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
 * save_evidence.php
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
 * Class save_evidence.
 */
class save_evidence extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID.'),
            'questionid' => new external_value(PARAM_INT, 'Question ID.'),
            'evidenceid' => new external_value(PARAM_INT, 'Evidence ID, or zero for a new item.'),
            'starttime' => new external_value(PARAM_FLOAT, 'Evidence start time.'),
            'endtime' => new external_value(PARAM_FLOAT, 'Evidence end time.'),
            'justification' => new external_value(PARAM_TEXT, 'Student justification.'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param int $questionid Parameter questionid.
     * @param int $evidenceid Parameter evidenceid.
     * @param float $starttime Parameter starttime.
     * @param float $endtime Parameter endtime.
     * @param string $justification Parameter justification.
     * @return array Return value.
     */
    public static function execute(int $cmid, int $questionid, int $evidenceid, float $starttime,
                                   float $endtime, string $justification): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact(
            'cmid', 'questionid', 'evidenceid', 'starttime', 'endtime', 'justification'
        ));
        $cm = get_coursemodule_from_id('videoevidence', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoevidence:view', $context);
        $question = $DB->get_record('videoevidence_questions', [
            'id' => $params['questionid'], 'videoevidenceid' => $cm->instance,
        ], '*', MUST_EXIST);
        $answer = grading_manager::answer($question, $USER->id);
        if ($answer->status === 'graded') {
            throw new \moodle_exception('answerlocked', 'videoevidence');
        }
        if ($params['starttime'] < 0 || $params['endtime'] < $params['starttime'] || trim($params['justification']) === '') {
            throw new \invalid_parameter_exception('Invalid evidence interval or justification.');
        }
        if ($question->selectiontype === 'moment') {
            $params['endtime'] = $params['starttime'];
        }
        if ($params['evidenceid']) {
            $item = $DB->get_record('videoevidence_evidence', [
                'id' => $params['evidenceid'], 'answerid' => $answer->id,
            ], '*', MUST_EXIST);
            $item->starttime = $params['starttime'];
            $item->endtime = $params['endtime'];
            $item->justification = $params['justification'];
            $item->timemodified = time();
            $DB->update_record('videoevidence_evidence', $item);
        } else {
            $count = $DB->count_records('videoevidence_evidence', ['answerid' => $answer->id]);
            if ($count >= (int)$question->maxevidence) {
                throw new \moodle_exception('maxevidencereached', 'videoevidence');
            }
            $item = (object)[
                'answerid' => $answer->id, 'starttime' => $params['starttime'], 'endtime' => $params['endtime'],
                'justification' => $params['justification'], 'sortorder' => $count,
                'timecreated' => time(), 'timemodified' => time(),
            ];
            $item->id = $DB->insert_record('videoevidence_evidence', $item);
        }
        grading_manager::reset_to_draft($answer);
        grading_manager::sync($question->videoevidenceid, $USER->id);
        return ['id' => (int)$item->id, 'status' => 'draft'];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Evidence ID.'),
            'status' => new external_value(PARAM_ALPHA, 'Answer status.'),
        ]);
    }
}
