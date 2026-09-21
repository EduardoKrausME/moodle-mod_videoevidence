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
 * services.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_videoevidence_update_progress' => [
        'classname' => '\\mod_videoevidence\\external\\update_progress',
        'methodname' => 'execute',
        'description' => 'Updates Video Evidence playback progress.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoevidence:view',
    ],
    'mod_videoevidence_save_evidence' => [
        'classname' => '\\mod_videoevidence\\external\\save_evidence',
        'methodname' => 'execute',
        'description' => 'Creates or updates one video evidence selection.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoevidence:view',
    ],
    'mod_videoevidence_delete_evidence' => [
        'classname' => '\\mod_videoevidence\\external\\delete_evidence',
        'methodname' => 'execute',
        'description' => 'Deletes one video evidence selection.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoevidence:view',
    ],
    'mod_videoevidence_submit_answer' => [
        'classname' => '\\mod_videoevidence\\external\\submit_answer',
        'methodname' => 'execute',
        'description' => 'Submits an answer and calculates automatic criteria.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoevidence:view',
    ],
];
