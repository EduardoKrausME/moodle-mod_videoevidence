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
 * view.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoevidence\timecode;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videoevidence', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videoevidence', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoevidence:view', $context);
$PAGE->set_url('/mod/videoevidence/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}
$progress = $DB->get_record('videoevidence_progress', ['videoevidenceid' => $activity->id, 'userid' => $USER->id]);
if (!$progress) {
    $progress = (object)['duration' => 0, 'lastposition' => 0, 'percent' => 0, 'watchedsegments' => '[]', 'sequence' => 0];
}
$questions = $DB->get_records('videoevidence_questions', ['videoevidenceid' => $activity->id], 'sortorder,id');
$questiondata = [];
$number = 0;
foreach ($questions as $question) {
    $number++;
    $answer = $DB->get_record('videoevidence_answers', ['questionid' => $question->id, 'userid' => $USER->id]);
    $evidenceitems = [];
    if ($answer) {
        foreach ($DB->get_records('videoevidence_evidence', ['answerid' => $answer->id], 'sortorder,id') as $item) {
            $evidenceitems[] = [
                'id' => $item->id,
                'start' => timecode::format((float)$item->starttime),
                'end' => timecode::format((float)$item->endtime),
                'startseconds' => (float)$item->starttime,
                'endseconds' => (float)$item->endtime,
                'justification' => s($item->justification),
                'ismoment' => $question->selectiontype === 'moment',
            ];
        }
    }
    $status = $answer->status ?? 'draft';
    $questiondata[] = [
        'id' => $question->id,
        'number' => $number,
        'questiontext' => format_text($question->questiontext, $question->questionformat, ['context' => $context]),
        'minevidence' => $question->minevidence,
        'maxevidence' => $question->maxevidence,
        'required' => (bool)$question->required,
        'ismoment' => $question->selectiontype === 'moment',
        'evidence' => $evidenceitems,
        'hasevidence' => (bool)$evidenceitems,
        'canadd' => $status !== 'graded' && count($evidenceitems) < (int)$question->maxevidence,
        'locked' => $status === 'graded',
        'submitted' => in_array($status, ['submitted', 'graded'], true),
        'statuslabel' => get_string('status' . $status, 'videoevidence'),
        'score' => ($answer && $answer->finalscore !== null) ? format_float($answer->finalscore, 1) : '',
        'hasscore' => $answer && $answer->finalscore !== null,
        'feedback' => $answer ? format_text((string)$answer->feedback, FORMAT_PLAIN) : '',
        'hasfeedback' => $answer && trim((string)$answer->feedback) !== '',
    ];
}
$source = \mod_videoevidence\source_manager::player_data($activity, $context);
$config = [
    'cmid' => $cm->id,
    'source' => $source,
    'lastposition' => (float)$progress->lastposition,
    'resumeplayback' => (int)$activity->resumeplayback,
    'allowseek' => (bool)$activity->allowseek,
    'segments' => json_decode((string)$progress->watchedsegments, true) ?: [],
    'sequence' => (int)$progress->sequence,
    'resumequestion' => get_string('resumequestion', 'videoevidence', timecode::format((float)$progress->lastposition)),
];
$data = [
    'name' => format_string($activity->name),
    'intro' => format_module_intro('videoevidence', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'player' => $source,
    'questions' => $questiondata,
    'hasquestions' => (bool)$questiondata,
    'progresspercent' => round((float)$progress->percent, 1),
    'configjson' => json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    'canmanage' => has_capability('mod/videoevidence:managequestions', $context),
    'manageurl' => (new moodle_url('/mod/videoevidence/question/index.php', ['id' => $cm->id]))->out(false),
    'canreport' => has_capability('mod/videoevidence:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videoevidence/report/report.php', ['id' => $cm->id]))->out(false),
];
$PAGE->requires->js_call_amd('mod_videoevidence/app', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoevidence/view', $data);
echo $OUTPUT->footer();
