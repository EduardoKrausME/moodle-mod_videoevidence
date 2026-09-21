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
 * report.php
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
require_capability('mod/videoevidence:viewreport', $context);
$PAGE->set_url('/mod/videoevidence/report/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('report', 'videoevidence'));
$PAGE->set_heading(format_string($course->fullname));

$questions = $DB->get_records('videoevidence_questions', ['videoevidenceid' => $activity->id], 'sortorder,id');
$users = get_enrolled_users($context, 'mod/videoevidence:view', 0, 'u.id,u.firstname,u.lastname,u.email');
$rows = [];
foreach ($users as $user) {
    if (has_capability('mod/videoevidence:grade', $context, $user->id) ||
        has_capability('mod/videoevidence:managequestions', $context, $user->id)) {
        continue;
    }
    $answered = 0;
    $evidencecount = 0;
    $submitted = 0;
    foreach ($questions as $question) {
        $answer = $DB->get_record('videoevidence_answers', ['questionid' => $question->id, 'userid' => $user->id]);
        if ($answer) {
            $count = $DB->count_records('videoevidence_evidence', ['answerid' => $answer->id]);
            if ($count > 0) {
                $answered++;
            }
            $evidencecount += $count;
            if (in_array($answer->status, ['submitted', 'graded'], true)) {
                $submitted++;
            }
        }
    }
    $progress = $DB->get_record('videoevidence_progress', ['videoevidenceid' => $activity->id, 'userid' => $user->id]);
    $score = \mod_videoevidence\grading_manager::activity_score($activity->id, $user->id);
    $rows[] = [
        'fullname' => fullname($user),
        'email' => $user->email,
        'answered' => $answered,
        'questioncount' => count($questions),
        'submitted' => $submitted,
        'evidencecount' => $evidencecount,
        'percent' => $progress ? round((float)$progress->percent, 1) : 0,
        'score' => $score === null ? '—' : format_float($score, 1) . '%',
        'gradeurl' => (new moodle_url('/mod/videoevidence/report/grade.php', ['id' => $cm->id, 'userid' => $user->id]))->out(false),
    ];
}
$data = [
    'name' => format_string($activity->name),
    'rows' => $rows,
    'hasrows' => (bool)$rows,
    'viewurl' => (new moodle_url('/mod/videoevidence/view.php', ['id' => $cm->id]))->out(false),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoevidence/report', $data);
echo $OUTPUT->footer();
