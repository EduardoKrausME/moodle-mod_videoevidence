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
 * delete_evidence.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videoevidence\grading_manager;

/**
 * Class delete_evidence.
 */
class delete_evidence extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID.'),
            'evidenceid' => new external_value(PARAM_INT, 'Evidence ID.'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param int $evidenceid Parameter evidenceid.
     * @return array Return value.
     */
    public static function execute(int $cmid, int $evidenceid): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'evidenceid'));
        $cm = get_coursemodule_from_id('videoevidence', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoevidence:view', $context);
        $sql = "SELECT e.*, a.status, a.userid, a.id AS answerid, q.videoevidenceid
                  FROM {videoevidence_evidence} e
                  JOIN {videoevidence_answers} a ON a.id = e.answerid
                  JOIN {videoevidence_questions} q ON q.id = a.questionid
                 WHERE e.id = :evidenceid AND a.userid = :userid AND q.videoevidenceid = :activityid";
        $item = $DB->get_record_sql($sql, [
            'evidenceid' => $params['evidenceid'], 'userid' => $USER->id, 'activityid' => $cm->instance,
        ], MUST_EXIST);
        if ($item->status === 'graded') {
            throw new \moodle_exception('answerlocked', 'videoevidence');
        }
        $answer = $DB->get_record('videoevidence_answers', ['id' => $item->answerid], '*', MUST_EXIST);
        $DB->delete_records('videoevidence_evidence', ['id' => $item->id]);
        grading_manager::reset_to_draft($answer);
        grading_manager::sync($item->videoevidenceid, $USER->id);
        return ['deleted' => true];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(['deleted' => new external_value(PARAM_BOOL, 'Deletion result.')]);
    }
}
