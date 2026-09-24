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
     * @return \stdClass Dashboard aggregate data.
     */
    public static function get_by_cmid(int $cmid): \stdClass {
        global $DB;

        $meetings = meeting_service::get_all_by_cmid($cmid);
        if (empty($meetings)) {
            return self::empty_data();
        }

        $meetingids = array_map(static fn(\stdClass $meeting): int => (int) $meeting->id, $meetings);
        $attendees = $DB->get_records_list(
            'bbbext_bnx_datahub_meeting_user',
            'meetingid',
            $meetingids,
            '',
            'id, meetingid, userid, fullname, attendeerole, elapsedtime, engchats, engtalks, '
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
                'label' => userdate((int) $meeting->timestart, get_string('strftimedateshort', 'langconfig')),
                'learnercount' => count($learners),
                'medianattendance' => self::median(array_map(
                    static fn(\stdClass $learner): int => (int) $learner->elapsedtime,
                    $learners
                )),
            ];
        }

        $latestmeeting = reset($meetings);
        $latestlearners = self::latest_session_learners($learnersbymeeting[(int) $latestmeeting->id]);

        return (object) [
            'hasdata' => true,
            'meetingcount' => count($meetings),
            'uniquelearnercount' => count($uniqueusers),
            'medianattendance' => self::median($durations),
            'pollparticipantcount' => count($pollparticipants),
            'metrics' => (object) $metrics,
            'sessions' => $sessions,
            'latestmeeting' => $latestmeeting,
            'latestlearners' => $latestlearners,
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
     * Return the most active learners from the latest meeting.
     *
     * @param \stdClass[] $learners Latest meeting learner rows.
     * @return \stdClass[] Chart-ready learner summaries.
     */
    private static function latest_session_learners(array $learners): array {
        $summaries = array_map(static function (\stdClass $learner): \stdClass {
            return (object) [
                'fullname' => $learner->fullname,
                'chats' => (int) $learner->engchats,
                'talks' => (int) $learner->engtalks,
                'raisedhands' => (int) $learner->engraisehand,
                'pollvotes' => (int) $learner->engpollvotes,
            ];
        }, $learners);

        usort($summaries, static function (\stdClass $left, \stdClass $right): int {
            $lefttotal = $left->chats + $left->talks + $left->raisedhands + $left->pollvotes;
            $righttotal = $right->chats + $right->talks + $right->raisedhands + $right->pollvotes;

            return $righttotal <=> $lefttotal ?: strcasecmp($left->fullname, $right->fullname);
        });

        return array_slice($summaries, 0, self::LATEST_SESSION_LEARNER_LIMIT);
    }
}
