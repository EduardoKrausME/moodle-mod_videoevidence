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
 * grade.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoevidence\timecode;

require('../../../config.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$cm = get_coursemodule_from_id('videoevidence', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoevidence', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
require_login($course, true, $cm);
require_capability('mod/videoevidence:grade', $context);
$PAGE->set_url('/mod/videoevidence/report/grade.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_title(get_string('grade', 'videoevidence'));
$PAGE->set_heading(format_string($course->fullname));

$questions = $DB->get_records('videoevidence_questions', ['videoevidenceid' => $activity->id], 'sortorder,id');
if (data_submitted() && confirm_sesskey()) {
    foreach ($questions as $question) {
        $answer = $DB->get_record('videoevidence_answers', ['questionid' => $question->id, 'userid' => $userid]);
        if (!$answer || !in_array($answer->status, ['submitted', 'graded'], true)) {
            continue;
        }
        foreach (['selectionscore', 'justificationscore', 'quantityscore'] as $field) {
            $name = $field . '_' . $answer->id;
            if (isset($_POST[$name]) && trim((string)$_POST[$name]) !== '') {
                $value = clean_param($_POST[$name], PARAM_FLOAT);
                $answer->{$field} = min(100, max(0, (float)$value));
            }
        }
        $feedbackname = 'feedback_' . $answer->id;
        $answer->feedback = isset($_POST[$feedbackname]) ? clean_param($_POST[$feedbackname], PARAM_TEXT) : $answer->feedback;
        \mod_videoevidence\grading_manager::recalculate($question, $answer);
        $answer->status = 'graded';
        $answer->grader = $USER->id;
        $answer->timegraded = time();
        $answer->timemodified = time();
        $DB->update_record('videoevidence_answers', $answer);
    }
    \mod_videoevidence\grading_manager::sync($activity->id, $userid);
    redirect($PAGE->url, get_string('gradessaved', 'videoevidence'));
}

$items = [];
$i = 0;
foreach ($questions as $question) {
    $i++;
    $answer = $DB->get_record('videoevidence_answers', ['questionid' => $question->id, 'userid' => $userid]);
    $evidence = [];
    if ($answer) {
        foreach ($DB->get_records('videoevidence_evidence', ['answerid' => $answer->id], 'sortorder,id') as $item) {
            $evidence[] = [
                'range' => $question->selectiontype === 'moment'
                    ? timecode::format((float)$item->starttime)
                    : timecode::format((float)$item->starttime) . ' – ' . timecode::format((float)$item->endtime),
                'justification' => s($item->justification),
                'start' => (float)$item->starttime,
            ];
        }
    }
    $items[] = [
        'number' => $i,
        'questiontext' => format_text($question->questiontext, $question->questionformat, ['context' => $context]),
        'answerid' => $answer ? $answer->id : 0,
        'hasanswer' => (bool)$answer,
        'can grade' => $answer && in_array($answer->status, ['submitted', 'graded'], true),
        'cangrade' => $answer && in_array($answer->status, ['submitted', 'graded'], true),
        'status' => $answer ? get_string('status' . $answer->status, 'videoevidence') : get_string('statusdraft', 'videoevidence'),
        'evidence' => $evidence,
        'hasevidence' => (bool)$evidence,
        'selectionscore' => ($answer && $answer->selectionscore !== null) ? $answer->selectionscore : '',
        'justificationscore' => ($answer && $answer->justificationscore !== null) ? $answer->justificationscore : '',
        'quantityscore' => ($answer && $answer->quantityscore !== null) ? $answer->quantityscore : '',
        'feedback' => $answer ? s((string)$answer->feedback) : '',
    ];
}
$data = [
    'name' => format_string($activity->name),
    'student' => fullname($user),
    'items' => $items,
    'sesskey' => sesskey(),
    'reporturl' => (new moodle_url('/mod/videoevidence/report/report.php', ['id' => $cm->id]))->out(false),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoevidence/grade', $data);
echo $OUTPUT->footer();
