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
 * backup_videoevidence_stepslib.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class backup_videoevidence_activity_structure_step.
 */
class backup_videoevidence_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return backup_nested_element Return value.
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');
        $activity = new backup_nested_element('videoevidence', ['id'], [
            'name', 'intro', 'introformat', 'videosource', 'videourl', 'sourceconfig', 'resumeplayback', 'allowseek',
            'completionevidence', 'grade', 'timecreated', 'timemodified'
        ]);
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'questiontext', 'questionformat', 'selectiontype', 'minevidence',
            'maxevidence', 'expectedstart', 'expectedend',
            'tolerance', 'selectionweight', 'justificationweight', 'quantityweight',
            'required', 'sortorder', 'timecreated', 'timemodified',
        ]);
        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', ['id'], [
            'userid', 'status', 'selectionscore', 'justificationscore', 'quantityscore', 'finalscore', 'feedback', 'timesubmitted',
            'timegraded', 'grader', 'timecreated', 'timemodified'
        ]);
        $evidences = new backup_nested_element('evidences');
        $evidence = new backup_nested_element('evidence', ['id'],
            ['starttime', 'endtime', 'justification', 'sortorder', 'timecreated', 'timemodified']);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'duration', 'lastposition', 'uniquewatched', 'totalwatchtime',
            'percent', 'watchedsegments', 'sequence', 'timecreated', 'timemodified',
        ]);
        $activity->add_child($questions);
        $questions->add_child($question);
        $question->add_child($answers);
        $answers->add_child($answer);
        $answer->add_child($evidences);
        $evidences->add_child($evidence);
        $activity->add_child($progresses);
        $progresses->add_child($progress);
        $activity->set_source_table('videoevidence', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('videoevidence_questions', ['videoevidenceid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $answer->set_source_table('videoevidence_answers', ['questionid' => backup::VAR_PARENTID]);
            $evidence->set_source_table('videoevidence_evidence', ['answerid' => backup::VAR_PARENTID]);
            $progress->set_source_table('videoevidence_progress', ['videoevidenceid' => backup::VAR_PARENTID]);
        }
        $answer->annotate_ids('user', 'userid');
        $answer->annotate_ids('user', 'grader');
        $progress->annotate_ids('user', 'userid');
        $activity->annotate_files('mod_videoevidence', 'video', null);
        $activity->annotate_files('mod_videoevidence', 'poster', null);
        return $this->prepare_activity_structure($activity);
    }
}
