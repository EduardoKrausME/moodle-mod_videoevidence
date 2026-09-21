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
 * move.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
$id = required_param('id', PARAM_INT);
$qid = required_param('q', PARAM_INT);
$dir = required_param('dir', PARAM_ALPHA);
require_sesskey();
$cm = get_coursemodule_from_id('videoevidence', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoevidence:managequestions', $context);
$questions = array_values($DB->get_records('videoevidence_questions', ['videoevidenceid' => $cm->instance], 'sortorder,id'));
$index = null;
foreach ($questions as $i => $question) {
    if ((int)$question->id === $qid) {
        $index = $i;
        break;
    }
}
if ($index !== null) {
    $other = $dir === 'up' ? $index - 1 : $index + 1;
    if (isset($questions[$other])) {
        [$questions[$index], $questions[$other]] = [$questions[$other], $questions[$index]];
        foreach ($questions as $sort => $question) {
            $DB->set_field('videoevidence_questions', 'sortorder', $sort, ['id' => $question->id]);
        }
    }
}
redirect(new moodle_url('/mod/videoevidence/question/index.php', ['id' => $cm->id]));
