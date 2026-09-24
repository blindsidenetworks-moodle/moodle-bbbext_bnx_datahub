<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace bbbext_bnx_datahub\output;

use core\chart_bar;
use core\chart_line;
use core\chart_series;
use core\output\renderable;
use core\output\templatable;
use moodle_url;

/**
 * Template data for the DataHub activity dashboard.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard implements renderable, templatable {
    /** @var \stdClass Activity dashboard aggregate data. */
    private \stdClass $data;

    /** @var int BigBlueButton course module ID. */
    private int $cmid;

    /**
     * Constructor.
     *
     * @param \stdClass $data Activity dashboard aggregate data.
     * @param int $cmid BigBlueButton course module ID.
     */
    public function __construct(\stdClass $data, int $cmid) {
        $this->data = $data;
        $this->cmid = $cmid;
    }

    /**
     * Export dashboard data for the Mustache template.
     *
     * @param object $output Moodle renderer.
     * @return \stdClass Template data.
     */
    public function export_for_template($output): \stdClass {
        $data = (object) [
            'title' => get_string('dashboard', 'bbbext_bnx_datahub'),
            'intro' => get_string('dashboardintro', 'bbbext_bnx_datahub'),
            'hasdata' => $this->data->hasdata,
            'attendanceurl' => (new moodle_url(
                '/mod/bigbluebuttonbn/extension/bnx_datahub/attendance.php',
                ['cmid' => $this->cmid]
            ))->out(false),
            'engagementurl' => (new moodle_url(
                '/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php',
                ['cmid' => $this->cmid]
            ))->out(false),
            'attendanceaction' => get_string('dashboard:viewattendance', 'bbbext_bnx_datahub'),
            'engagementaction' => get_string('dashboard:viewengagement', 'bbbext_bnx_datahub'),
        ];

        if (!$this->data->hasdata) {
            $data->emptytitle = get_string('dashboard:emptytitle', 'bbbext_bnx_datahub');
            $data->emptytext = get_string('dashboard:emptytext', 'bbbext_bnx_datahub');
            return $data;
        }

        $data->metrics = [
            (object) [
                'label' => get_string('dashboard:meetings', 'bbbext_bnx_datahub'),
                'value' => $this->data->meetingcount,
            ],
            (object) [
                'label' => get_string('dashboard:learners', 'bbbext_bnx_datahub'),
                'value' => $this->data->uniquelearnercount,
            ],
            (object) [
                'label' => get_string('dashboard:medianattendance', 'bbbext_bnx_datahub'),
                'value' => format_time($this->data->medianattendance),
            ],
            (object) [
                'label' => get_string('dashboard:pollparticipants', 'bbbext_bnx_datahub'),
                'value' => $this->data->pollparticipantcount,
            ],
        ];
        $data->attendancechart = $output->render($this->attendance_chart());
        $data->engagementchart = $output->render($this->engagement_chart());
        $data->learnerchart = $output->render($this->learner_chart());

        return $data;
    }

    /**
     * Build the attendance trend chart.
     *
     * @return chart_line Attendance chart.
     */
    private function attendance_chart(): chart_line {
        $chart = new chart_line();
        $chart->set_title(get_string('dashboard:attendancechart', 'bbbext_bnx_datahub'));
        $chart->set_labels(array_map(static fn(\stdClass $session): string => $session->label, $this->data->sessions));
        $chart->add_series(new chart_series(
            get_string('dashboard:learners', 'bbbext_bnx_datahub'),
            array_map(static fn(\stdClass $session): int => $session->learnercount, $this->data->sessions)
        ));
        $chart->get_yaxis(0, true)->set_label(get_string('dashboard:learners', 'bbbext_bnx_datahub'));

        return $chart;
    }

    /**
     * Build the aggregate engagement chart.
     *
     * @return chart_bar Engagement chart.
     */
    private function engagement_chart(): chart_bar {
        $chart = new chart_bar();
        $chart->set_horizontal(true);
        $chart->set_title(get_string('dashboard:engagementchart', 'bbbext_bnx_datahub'));
        $chart->set_labels([
            get_string('attendee:engchats', 'bbbext_bnx_datahub'),
            get_string('attendee:engtalks', 'bbbext_bnx_datahub'),
            get_string('attendee:engraisehand', 'bbbext_bnx_datahub'),
            get_string('attendee:engpollvotes', 'bbbext_bnx_datahub'),
        ]);
        $chart->add_series(new chart_series(get_string('engagement', 'bbbext_bnx_datahub'), [
            $this->data->metrics->chats,
            $this->data->metrics->talks,
            $this->data->metrics->raisedhands,
            $this->data->metrics->pollvotes,
        ]));

        return $chart;
    }

    /**
     * Build the latest-session learner participation chart.
     *
     * @return chart_bar Learner participation chart.
     */
    private function learner_chart(): chart_bar {
        $chart = new chart_bar();
        $chart->set_horizontal(true);
        $chart->set_stacked(true);
        $chart->set_title(get_string('dashboard:learnerchart', 'bbbext_bnx_datahub'));
        $chart->set_labels(array_map(static fn(\stdClass $learner): string => $learner->fullname, $this->data->latestlearners));

        foreach ([
            'chats' => 'attendee:engchats',
            'talks' => 'attendee:engtalks',
            'raisedhands' => 'attendee:engraisehand',
            'pollvotes' => 'attendee:engpollvotes',
        ] as $metric => $label) {
            $chart->add_series(new chart_series(
                get_string($label, 'bbbext_bnx_datahub'),
                array_map(static fn(\stdClass $learner): int => $learner->{$metric}, $this->data->latestlearners)
            ));
        }

        return $chart;
    }
}
