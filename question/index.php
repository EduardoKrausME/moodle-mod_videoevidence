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
 * index.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videoevidence', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoevidence', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoevidence:managequestions', $context);
$PAGE->set_url('/mod/videoevidence/question/index.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managequestions', 'videoevidence'));
$PAGE->set_heading(format_string($course->fullname));
$questions = $DB->get_records('videoevidence_questions', ['videoevidenceid' => $activity->id], 'sortorder,id');
$items = [];
$count = count($questions);
$i = 0;
foreach ($questions as $question) {
    $i++;
    $items[] = [
        'id' => $question->id,
        'number' => $i,
        'text' => format_text($question->questiontext, $question->questionformat, ['context' => $context]),
        'min' => $question->minevidence,
        'max' => $question->maxevidence,
        'required' => (bool)$question->required,
        'editurl' => (new moodle_url('/mod/videoevidence/question/edit.php',
            ['id' => $cm->id, 'q' => $question->id]))->out(false),
        'deleteurl' => (new moodle_url('/mod/videoevidence/question/delete.php',
            ['id' => $cm->id, 'q' => $question->id, 'sesskey' => sesskey()]))->out(false),
        'upurl' => $i > 1 ? (new moodle_url('/mod/videoevidence/question/move.php',
            ['id' => $cm->id, 'q' => $question->id, 'dir' => 'up', 'sesskey' => sesskey()]))->out(false) : '',
        'downurl' => $i < $count ? (new moodle_url('/mod/videoevidence/question/move.php',
            ['id' => $cm->id, 'q' => $question->id, 'dir' => 'down', 'sesskey' => sesskey()]))->out(false) : '',
    ];
}
$data = [
    'name' => format_string($activity->name),
    'questions' => $items,
    'hasquestions' => (bool)$items,
    'addurl' => (new moodle_url('/mod/videoevidence/question/edit.php', ['id' => $cm->id]))->out(false),
    'viewurl' => (new moodle_url('/mod/videoevidence/view.php', ['id' => $cm->id]))->out(false),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoevidence/manage_questions', $data);
echo $OUTPUT->footer();
