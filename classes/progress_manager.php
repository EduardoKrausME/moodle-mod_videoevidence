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

/**
 * progress_manager.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence;

use stdClass;

/**
 * Class progress_manager.
 */
class progress_manager {
    /**
     * Method update.
     *
     * @param stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @param array $data Parameter data.
     * @return stdClass Return value.
     */
    public static function update(stdClass $activity, int $userid, array $data): stdClass {
        global $DB;
        $progress = $DB->get_record('videoevidence_progress', [
            'videoevidenceid' => $activity->id,
            'userid' => $userid,
        ]);
        $now = time();
        if (!$progress) {
            $progress = (object)[
                'videoevidenceid' => $activity->id, 'userid' => $userid, 'duration' => 0,
                'lastposition' => 0, 'uniquewatched' => 0, 'totalwatchtime' => 0, 'percent' => 0,
                'watchedsegments' => '[]', 'sequence' => 0, 'timecreated' => $now, 'timemodified' => $now,
            ];
            $progress->id = $DB->insert_record('videoevidence_progress', $progress);
        }
        if ((int)$data['sequence'] <= (int)$progress->sequence) {
            return $progress;
        }
        $duration = max((float)$progress->duration, (float)$data['duration']);
        $start = max(0, (float)$data['segmentstart']);
        $end = min($duration > 0 ? $duration : (float)$data['segmentend'], max(0, (float)$data['segmentend']));
        $delta = $end - $start;
        $segments = json_decode((string)$progress->watchedsegments, true) ?: [];
        // Only continuous heartbeat-sized intervals are counted. Large seek jumps do not add watched content.
        if ($delta > 0 && $delta <= 15.0) {
            $segments = segment_manager::merge($segments, $start, $end);
            $progress->totalwatchtime += $delta;
        }
        $progress->duration = $duration;
        $progress->lastposition = max(0, min($duration ?: (float)$data['currentposition'], (float)$data['currentposition']));
        $progress->watchedsegments = json_encode($segments);
        $progress->uniquewatched = segment_manager::duration($segments);
        $progress->percent = $duration > 0 ? min(100, $progress->uniquewatched * 100 / $duration) : 0;
        $progress->sequence = (int)$data['sequence'];
        $progress->timemodified = $now;
        $DB->update_record('videoevidence_progress', $progress);
        return $progress;
    }
}
