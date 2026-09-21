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
 * submit_answer.php
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
 * Class submit_answer.
 */
class submit_answer extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID.'),
            'questionid' => new external_value(PARAM_INT, 'Question ID.'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param int $questionid Parameter questionid.
     * @return array Return value.
     */
    public static function execute(int $cmid, int $questionid): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'questionid'));
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
        $answer = grading_manager::submit($question, $answer);
        grading_manager::sync($question->videoevidenceid, $USER->id);
        return [
            'status' => $answer->status,
            'selectionscore' => $answer->selectionscore === null ? -1 : (float)$answer->selectionscore,
            'quantityscore' => $answer->quantityscore === null ? -1 : (float)$answer->quantityscore,
            'finalscore' => $answer->finalscore === null ? -1 : (float)$answer->finalscore,
        ];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Answer status.'),
            'selectionscore' => new external_value(PARAM_FLOAT, 'Automatic selection score, or -1 when manual.'),
            'quantityscore' => new external_value(PARAM_FLOAT, 'Quantity criterion score.'),
            'finalscore' => new external_value(PARAM_FLOAT, 'Final score, or -1 when manual grading remains.'),
        ]);
    }
}
