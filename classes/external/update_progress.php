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
 * update_progress.php
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
use mod_videoevidence\progress_manager;

/**
 * Class update_progress.
 */
class update_progress extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID.'),
            'currentposition' => new external_value(PARAM_FLOAT, 'Current player position.'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration.'),
            'segmentstart' => new external_value(PARAM_FLOAT, 'Start of continuous watched interval.'),
            'segmentend' => new external_value(PARAM_FLOAT, 'End of continuous watched interval.'),
            'sequence' => new external_value(PARAM_INT, 'Monotonic client sequence.'),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param float $currentposition Parameter currentposition.
     * @param float $duration Parameter duration.
     * @param float $segmentstart Parameter segmentstart.
     * @param float $segmentend Parameter segmentend.
     * @param int $sequence Parameter sequence.
     * @return array Return value.
     */
    public static function execute(int $cmid, float $currentposition, float $duration,
                                   float $segmentstart, float $segmentend, int $sequence): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact(
            'cmid', 'currentposition', 'duration', 'segmentstart', 'segmentend', 'sequence'
        ));
        $cm = get_coursemodule_from_id('videoevidence', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoevidence:view', $context);
        if (isguestuser() || !is_enrolled($context, $USER, 'mod/videoevidence:view', true)) {
            throw new \required_capability_exception($context, 'mod/videoevidence:view', 'nopermissions', '');
        }
        if ($params['duration'] <= 0 || $params['duration'] > 604800 || $params['currentposition'] < 0 ||
            $params['segmentstart'] < 0 || $params['segmentend'] < 0 || $params['sequence'] < 1) {
            throw new \invalid_parameter_exception('Invalid playback tracking values.');
        }
        $activity = $DB->get_record('videoevidence', ['id' => $cm->instance], '*', MUST_EXIST);
        $progress = progress_manager::update($activity, $USER->id, $params);
        return [
            'percent' => (float)$progress->percent,
            'lastposition' => (float)$progress->lastposition,
            'sequence' => (int)$progress->sequence,
            'segments' => (string)$progress->watchedsegments,
        ];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_FLOAT, 'Unique watched percentage.'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Last accepted player position.'),
            'sequence' => new external_value(PARAM_INT, 'Last accepted sequence.'),
            'segments' => new external_value(PARAM_RAW, 'JSON watched segments.'),
        ]);
    }
}
