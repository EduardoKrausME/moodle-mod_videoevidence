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
 * delete.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

$id = required_param('id', PARAM_INT);
$qid = required_param('q', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('videoevidence', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videoevidence:managequestions', $context);

$question = $DB->get_record(
    'videoevidence_questions',
    ['id' => $qid, 'videoevidenceid' => $cm->instance],
    '*',
    MUST_EXIST
);

$indexurl = new moodle_url('/mod/videoevidence/question/index.php', ['id' => $cm->id]);
if (!$confirm) {
    $PAGE->set_url('/mod/videoevidence/question/delete.php', ['id' => $cm->id, 'q' => $qid]);
    $PAGE->set_title(get_string('deletequestionconfirm', 'videoevidence'));
    $PAGE->set_heading(format_string($course->fullname));

    $confirmurl = new moodle_url('/mod/videoevidence/question/delete.php', [
        'id' => $cm->id,
        'q' => $qid,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deletequestionconfirm', 'videoevidence'), $confirmurl, $indexurl);
    echo $OUTPUT->footer();
    exit;
}

require_sesskey();

$affecteduserids = $DB->get_fieldset_sql(
    'SELECT DISTINCT userid
       FROM {videoevidence_answers}
      WHERE questionid = :questionid',
    ['questionid' => $qid]
);
$answerids = $DB->get_fieldset_select('videoevidence_answers', 'id', 'questionid = :qid', ['qid' => $qid]);
if ($answerids) {
    [$sql, $params] = $DB->get_in_or_equal($answerids, SQL_PARAMS_NAMED, 'a');
    $DB->delete_records_select('videoevidence_evidence', "answerid {$sql}", $params);
}
$DB->delete_records('videoevidence_answers', ['questionid' => $qid]);
$DB->delete_records('videoevidence_questions', ['id' => $qid]);

foreach ($affecteduserids as $userid) {
    \mod_videoevidence\grading_manager::sync($cm->instance, (int)$userid);
}

redirect($indexurl, get_string('questiondeleted', 'videoevidence'));
