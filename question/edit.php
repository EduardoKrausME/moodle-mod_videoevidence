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
 * edit.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoevidence\form\question_form;
use mod_videoevidence\timecode;

require('../../../config.php');
$id = required_param('id', PARAM_INT);
$qid = optional_param('q', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videoevidence', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoevidence', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoevidence:managequestions', $context);
$PAGE->set_url('/mod/videoevidence/question/edit.php', ['id' => $cm->id, 'q' => $qid]);
$PAGE->set_title(get_string($qid ? 'editquestion' : 'addquestion', 'videoevidence'));
$PAGE->set_heading(format_string($course->fullname));
$question = $qid ? $DB->get_record('videoevidence_questions',
    ['id' => $qid, 'videoevidenceid' => $activity->id], '*', MUST_EXIST) : null;
$form = new question_form(null, []);
if ($question) {
    $defaults = clone $question;
    $defaults->questiontext_editor = ['text' => $question->questiontext, 'format' => $question->questionformat];
    $defaults->expectedstarttext = $question->expectedstart === null ? '' : timecode::format((float)$question->expectedstart);
    $defaults->expectedendtext = $question->expectedend === null ? '' : timecode::format((float)$question->expectedend);
    $form->set_data($defaults);
}
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videoevidence/question/index.php', ['id' => $cm->id]));
}
if ($data = $form->get_data()) {
    $record = $question ?: new stdClass();
    $record->videoevidenceid = $activity->id;
    $record->questiontext = $data->questiontext_editor['text'];
    $record->questionformat = $data->questiontext_editor['format'];
    $record->selectiontype = $data->selectiontype;
    $record->minevidence = $data->minevidence;
    $record->maxevidence = $data->maxevidence;
    $record->expectedstart = trim($data->expectedstarttext) === '' ? null : timecode::parse($data->expectedstarttext);
    $record->expectedend = trim($data->expectedendtext) === '' ? null : timecode::parse($data->expectedendtext);
    $record->tolerance = $data->tolerance;
    $record->selectionweight = $data->selectionweight;
    $record->justificationweight = $data->justificationweight;
    $record->quantityweight = $data->quantityweight;
    $record->required = $data->required ? 1 : 0;
    $record->timemodified = time();
    if ($question) {
        $record->id = $question->id;
        $DB->update_record('videoevidence_questions', $record);
    } else {
        $record->sortorder = (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), -1) + 1 FROM {videoevidence_questions} WHERE videoevidenceid = ?',
            [$activity->id]);
        $record->timecreated = time();
        $DB->insert_record('videoevidence_questions', $record);
    }
    redirect(new moodle_url('/mod/videoevidence/question/index.php',
        ['id' => $cm->id]), get_string('questionsaved', 'videoevidence'));
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
