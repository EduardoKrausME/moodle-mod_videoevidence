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
 * provider.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Class provider.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videoevidence_answers', [
            'userid' => 'privacy:metadata:answers:userid',
            'status' => 'privacy:metadata:answers:status',
            'selectionscore' => 'privacy:metadata:answers:selectionscore',
            'justificationscore' => 'privacy:metadata:answers:justificationscore',
            'quantityscore' => 'privacy:metadata:answers:quantityscore',
            'finalscore' => 'privacy:metadata:answers:finalscore',
            'feedback' => 'privacy:metadata:answers:feedback',
            'timesubmitted' => 'privacy:metadata:answers:timesubmitted',
            'timegraded' => 'privacy:metadata:answers:timegraded',
            'grader' => 'privacy:metadata:answers:grader',
            'timecreated' => 'privacy:metadata:answers:timecreated',
            'timemodified' => 'privacy:metadata:answers:timemodified',
        ], 'privacy:metadata:answers');
        $collection->add_database_table('videoevidence_evidence', [
            'starttime' => 'privacy:metadata:evidence:starttime',
            'endtime' => 'privacy:metadata:evidence:endtime',
            'justification' => 'privacy:metadata:evidence:justification',
            'timecreated' => 'privacy:metadata:evidence:timecreated',
            'timemodified' => 'privacy:metadata:evidence:timemodified',
        ], 'privacy:metadata:evidence');
        $collection->add_database_table('videoevidence_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'duration' => 'privacy:metadata:progress:duration',
            'lastposition' => 'privacy:metadata:progress:lastposition',
            'uniquewatched' => 'privacy:metadata:progress:uniquewatched',
            'totalwatchtime' => 'privacy:metadata:progress:totalwatchtime',
            'percent' => 'privacy:metadata:progress:percent',
            'watchedsegments' => 'privacy:metadata:progress:watchedsegments',
            'sequence' => 'privacy:metadata:progress:sequence',
            'timecreated' => 'privacy:metadata:progress:timecreated',
            'timemodified' => 'privacy:metadata:progress:timemodified',
        ], 'privacy:metadata:progress');
        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "
            SELECT DISTINCT ctx.id
              FROM {context}              ctx
              JOIN {course_modules}        cm ON cm.id = ctx.instanceid
              JOIN {modules}                m ON m.id = cm.module
              JOIN {videoevidence}          v ON v.id = cm.instance
         LEFT JOIN {videoevidence_progress} p ON p.videoevidenceid = v.id
                                              AND p.userid = :puserid
         LEFT JOIN {videoevidence_questions} q ON q.videoevidenceid = v.id
         LEFT JOIN {videoevidence_answers}   a ON a.questionid = q.id
                                              AND (a.userid = :auserid OR a.grader = :graderid)
             WHERE ctx.contextlevel = :contextlevel
               AND m.name = :modname
               AND (p.id IS NOT NULL OR a.id IS NOT NULL)";
        $contextlist->add_from_sql($sql, [
            'puserid' => $userid,
            'auserid' => $userid,
            'graderid' => $userid,
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videoevidence',
        ]);
        return $contextlist;
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videoevidence', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            $progress = $DB->get_record('videoevidence_progress', [
                'videoevidenceid' => $cm->instance,
                'userid' => $userid,
            ]);

            $answers = [];
            $grading = [];
            $questions = $DB->get_records('videoevidence_questions', ['videoevidenceid' => $cm->instance], 'sortorder,id');
            foreach ($questions as $question) {
                $answer = $DB->get_record('videoevidence_answers', [
                    'questionid' => $question->id,
                    'userid' => $userid,
                ]);
                if ($answer) {
                    $answerdata = clone $answer;
                    $answerdata->evidence = array_values($DB->get_records(
                        'videoevidence_evidence',
                        ['answerid' => $answer->id],
                        'sortorder,id'
                    ));
                    $answers[] = $answerdata;
                }

                $gradedanswers = $DB->get_records('videoevidence_answers', [
                    'questionid' => $question->id,
                    'grader' => $userid,
                ]);
                foreach ($gradedanswers as $gradedanswer) {
                    if ((int)$gradedanswer->userid !== $userid) {
                        $grading[] = $gradedanswer;
                    }
                }
            }

            if (!$progress && !$answers && !$grading) {
                continue;
            }

            $data = (object)[
                'progress' => $progress ?: null,
                'answers' => $answers,
                'grading' => $grading,
            ];
            writer::with_context($context)->export_data([get_string('pluginname', 'videoevidence')], $data);
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param \context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('videoevidence', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $questionids = $DB->get_fieldset_select('videoevidence_questions', 'id', 'videoevidenceid = :id', ['id' => $cm->instance]);
        if ($questionids) {
            [$sql, $params] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'q');
            $answerids = $DB->get_fieldset_select('videoevidence_answers', 'id', "questionid {$sql}", $params);
            if ($answerids) {
                [$asql, $aparams] = $DB->get_in_or_equal($answerids, SQL_PARAMS_NAMED, 'a');
                $DB->delete_records_select('videoevidence_evidence', "answerid {$asql}", $aparams);
            }
            $DB->delete_records_select('videoevidence_answers', "questionid {$sql}", $params);
        }
        $DB->delete_records('videoevidence_progress', ['videoevidenceid' => $cm->instance]);
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videoevidence', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $questionids = $DB->get_fieldset_select('videoevidence_questions', 'id',
                'videoevidenceid = :id', ['id' => $cm->instance]);
            if ($questionids) {
                [$qsql, $qparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'q');

                $answerparams = $qparams;
                $answerparams['userid'] = $userid;
                $answerids = $DB->get_fieldset_select(
                    'videoevidence_answers',
                    'id',
                    "questionid {$qsql} AND userid = :userid",
                    $answerparams
                );
                if ($answerids) {
                    [$asql, $aparams] = $DB->get_in_or_equal($answerids, SQL_PARAMS_NAMED, 'a');
                    $DB->delete_records_select('videoevidence_evidence', "answerid {$asql}", $aparams);
                    $DB->delete_records_select('videoevidence_answers', "id {$asql}", $aparams);
                }

                $graderparams = $qparams;
                $graderparams['grader'] = $userid;
                $DB->set_field_select(
                    'videoevidence_answers',
                    'grader',
                    0,
                    "questionid {$qsql} AND grader = :grader",
                    $graderparams
                );
            }
            $DB->delete_records('videoevidence_progress', ['videoevidenceid' => $cm->instance, 'userid' => $userid]);
        }
    }

    /**
     * Method get_users_in_context.
     *
     * @param userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $progresssql = "
            SELECT p.userid
              FROM {course_modules}         cm
              JOIN {videoevidence_progress}  p ON p.videoevidenceid = cm.instance
             WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $progresssql, ['cmid' => $context->instanceid]);

        $answersql = "
            SELECT a.userid
              FROM {course_modules}          cm
              JOIN {videoevidence_questions} q ON q.videoevidenceid = cm.instance
              JOIN {videoevidence_answers}   a ON a.questionid = q.id
             WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $answersql, ['cmid' => $context->instanceid]);

        $gradersql = "
            SELECT a.grader AS userid
              FROM {course_modules}          cm
              JOIN {videoevidence_questions} q ON q.videoevidenceid = cm.instance
              JOIN {videoevidence_answers}   a ON a.questionid = q.id
             WHERE cm.id = :cmid
               AND a.grader <> 0";
        $userlist->add_from_sql('userid', $gradersql, ['cmid' => $context->instanceid]);
    }

    /**
     * Method delete_data_for_users.
     *
     * @param approved_userlist $userlist Parameter userlist.
     * @return void Return value.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('videoevidence', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        $questionids = $DB->get_fieldset_select('videoevidence_questions', 'id', 'videoevidenceid = :id', ['id' => $cm->instance]);
        foreach ($userids as $userid) {
            if ($questionids) {
                [$qsql, $qparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'q');

                $answerparams = $qparams;
                $answerparams['userid'] = $userid;
                $answerids = $DB->get_fieldset_select(
                    'videoevidence_answers',
                    'id',
                    "questionid {$qsql} AND userid = :userid",
                    $answerparams
                );
                if ($answerids) {
                    [$asql, $aparams] = $DB->get_in_or_equal($answerids, SQL_PARAMS_NAMED, 'a');
                    $DB->delete_records_select('videoevidence_evidence', "answerid {$asql}", $aparams);
                    $DB->delete_records_select('videoevidence_answers', "id {$asql}", $aparams);
                }

                $graderparams = $qparams;
                $graderparams['grader'] = $userid;
                $DB->set_field_select(
                    'videoevidence_answers',
                    'grader',
                    0,
                    "questionid {$qsql} AND grader = :grader",
                    $graderparams
                );
            }
            $DB->delete_records('videoevidence_progress', ['videoevidenceid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
