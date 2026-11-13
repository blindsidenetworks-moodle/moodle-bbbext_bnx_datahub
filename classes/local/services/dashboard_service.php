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

namespace bbbext_bnx_datahub\local\services;

/**
 * Activity-scoped aggregates for the DataHub dashboard.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_service {
    /** Maximum learner rows returned for the latest-session chart. */
    public const LATEST_SESSION_LEARNER_LIMIT = 12;

    /**
     * Return dashboard data for a BigBlueButton course module.
     *
     * @param int $cmid Course module ID.
     * @param int $selectedmeetingid Selected DataHub meeting ID, or zero for the latest meeting.
     * @return \stdClass Dashboard aggregate data.
     */
    public static function get_by_cmid(int $cmid, int $selectedmeetingid = 0): \stdClass {
        global $DB;

        $allmeetings = meeting_service::get_all_by_cmid($cmid);
        $meetings = array_values(array_filter($allmeetings, static fn(\stdClass $meeting): bool => !(bool) $meeting->excluded));
        $excludedcount = count($allmeetings) - count($meetings);
        if (empty($meetings)) {
            $data = self::empty_data();
            $data->excludedcount = $excludedcount;
            return $data;
        }

        $meetingids = array_map(static fn(\stdClass $meeting): int => (int) $meeting->id, $meetings);
        $attendees = $DB->get_records_list(
            'bbbext_bnx_datahub_meeting_user',
            'meetingid',
            $meetingids,
            '',
            'id, meetingid, userid, fullname, attendeerole, elapsedtime, attendeescore, engchats, engtalks, '
                . 'engraisehand, engemojis, engpollvotes, engtalktime'
        );

        $learnersbymeeting = array_fill_keys($meetingids, []);
        foreach ($attendees as $attendee) {
            if (self::is_moderator($attendee)) {
                continue;
            }
            $learnersbymeeting[(int) $attendee->meetingid][] = $attendee;
        }

        $uniqueusers = [];
        $pollparticipants = [];
        $durations = [];
        $metrics = [
            'chats' => 0,
            'talks' => 0,
            'raisedhands' => 0,
            'emojis' => 0,
            'pollvotes' => 0,
            'talktime' => 0,
        ];

        foreach ($learnersbymeeting as $learners) {
            foreach ($learners as $learner) {
                $userid = (int) $learner->userid;
                if ($userid > 0) {
                    $uniqueusers[$userid] = true;
                }
                if ((int) $learner->engpollvotes > 0 && $userid > 0) {
                    $pollparticipants[$userid] = true;
                }
                $durations[] = (int) $learner->elapsedtime;
                $metrics['chats'] += (int) $learner->engchats;
                $metrics['talks'] += (int) $learner->engtalks;
                $metrics['raisedhands'] += (int) $learner->engraisehand;
                $metrics['emojis'] += (int) $learner->engemojis;
                $metrics['pollvotes'] += (int) $learner->engpollvotes;
                $metrics['talktime'] += (int) $learner->engtalktime;
            }
        }

        $sessions = [];
        foreach (array_reverse($meetings) as $meeting) {
            $learners = $learnersbymeeting[(int) $meeting->id];
            $sessions[] = (object) [
                'id' => (int) $meeting->id,
                'label' => userdate((int) $meeting->timestart),
                'learnercount' => count($learners),
                'medianattendance' => self::median(array_map(
                    static fn(\stdClass $learner): int => (int) $learner->elapsedtime,
                    $learners
                )),
            ];
        }

        $selectedmeeting = self::select_meeting($meetings, $selectedmeetingid);
        $selectedlearners = self::session_learners($learnersbymeeting[(int) $selectedmeeting->id]);

        return (object) [
            'hasdata' => true,
            'excludedcount' => $excludedcount,
            'meetingcount' => count($meetings),
            'uniquelearnercount' => count($uniqueusers),
            'medianattendance' => self::median($durations),
            'pollparticipantcount' => count($pollparticipants),
            'metrics' => (object) $metrics,
            'sessions' => $sessions,
            'selectedmeeting' => $selectedmeeting,
            'selectedmetrics' => self::session_metrics($selectedlearners),
            'selectedlearners' => $selectedlearners,
            'latestmeeting' => $selectedmeeting,
            'latestlearners' => $selectedlearners,
        ];
    }

    /**
     * Return the dashboard shape when no meeting data has been stored.
     *
     * @return \stdClass Empty dashboard data.
     */
    private static function empty_data(): \stdClass {
        return (object) [
            'hasdata' => false,
            'meetingcount' => 0,
            'uniquelearnercount' => 0,
            'medianattendance' => 0,
            'pollparticipantcount' => 0,
            'metrics' => (object) [
                'chats' => 0,
                'talks' => 0,
                'raisedhands' => 0,
                'emojis' => 0,
                'pollvotes' => 0,
                'talktime' => 0,
            ],
            'sessions' => [],
            'selectedmeeting' => null,
            'selectedmetrics' => null,
            'selectedlearners' => [],
            'latestmeeting' => null,
            'latestlearners' => [],
        ];
    }

    /**
     * Determine whether an attendee row belongs to a moderator.
     *
     * @param \stdClass $attendee Attendee row.
     * @return bool Whether the attendee is a moderator.
     */
    private static function is_moderator(\stdClass $attendee): bool {
        return $attendee->attendeerole === 'moderator';
    }

    /**
     * Return the median value from a list of integer values.
     *
     * @param int[] $values Integer values.
     * @return int Median integer value.
     */
    private static function median(array $values): int {
        if (empty($values)) {
            return 0;
        }

        sort($values, SORT_NUMERIC);
        $middle = intdiv(count($values), 2);

        if (count($values) % 2 !== 0) {
            return $values[$middle];
        }

        return (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }

    /**
     * Return the requested meeting when it belongs to this activity, or the latest meeting.
     *
     * @param \stdClass[] $meetings Activity meeting rows ordered newest first.
     * @param int $selectedmeetingid Requested meeting ID.
     * @return \stdClass Selected meeting row.
     */
    private static function select_meeting(array $meetings, int $selectedmeetingid): \stdClass {
        if ($selectedmeetingid > 0) {
            foreach ($meetings as $meeting) {
                if ((int) $meeting->id === $selectedmeetingid) {
                    $meeting->id = (int) $meeting->id;
                    return $meeting;
                }
            }
        }

        $meeting = reset($meetings);
        $meeting->id = (int) $meeting->id;

        return $meeting;
    }

    /**
     * Return the most active learners from the selected meeting.
     *
     * @param \stdClass[] $learners Selected meeting learner rows.
     * @return \stdClass[] Dashboard learner summaries.
     */
    private static function session_learners(array $learners): array {
        $summaries = array_map(static function (\stdClass $learner): \stdClass {
            return (object) [
                'userid' => (int) $learner->userid,
                'fullname' => $learner->fullname,
                'elapsedtime' => (int) $learner->elapsedtime,
                'score' => (int) $learner->attendeescore,
                'chats' => (int) $learner->engchats,
                'talks' => (int) $learner->engtalks,
                'talktime' => (int) $learner->engtalktime,
                'raisedhands' => (int) $learner->engraisehand,
                'emojis' => (int) $learner->engemojis,
                'pollvotes' => (int) $learner->engpollvotes,
            ];
        }, $learners);

        usort($summaries, static function (\stdClass $left, \stdClass $right): int {
            $lefttotal = $left->chats + $left->talks + $left->raisedhands + $left->pollvotes;
            $righttotal = $right->chats + $right->talks + $right->raisedhands + $right->pollvotes;

            return $righttotal <=> $lefttotal ?: strcasecmp($left->fullname, $right->fullname);
        });

        return $summaries;
    }

    /**
     * Calculate selected-session summary metrics.
     *
     * @param \stdClass[] $learners Selected meeting learner summaries.
     * @return \stdClass Session metrics.
     */
    private static function session_metrics(array $learners): \stdClass {
        $metrics = (object) [
            'learnercount' => count($learners),
            'medianattendance' => self::median(array_column($learners, 'elapsedtime')),
            'pollparticipantcount' => 0,
            'chats' => 0,
            'talks' => 0,
            'talktime' => 0,
            'raisedhands' => 0,
            'emojis' => 0,
            'pollvotes' => 0,
        ];

        foreach ($learners as $learner) {
            $metrics->pollparticipantcount += $learner->pollvotes > 0 ? 1 : 0;
            $metrics->chats += $learner->chats;
            $metrics->talks += $learner->talks;
            $metrics->talktime += $learner->talktime;
            $metrics->raisedhands += $learner->raisedhands;
            $metrics->emojis += $learner->emojis;
            $metrics->pollvotes += $learner->pollvotes;
        }

        return $metrics;
    }
}
