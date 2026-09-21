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
            'feedback' => 'privacy:metadata:answers:feedback',
            'finalscore' => 'privacy:metadata:answers:finalscore',
        ], 'privacy:metadata:answers');
        $collection->add_database_table('videoevidence_evidence', [
            'starttime' => 'privacy:metadata:evidence:starttime',
            'endtime' => 'privacy:metadata:evidence:endtime',
            'justification' => 'privacy:metadata:evidence:justification',
        ], 'privacy:metadata:evidence');
        $collection->add_database_table('videoevidence_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'percent' => 'privacy:metadata:progress:percent',
            'lastposition' => 'privacy:metadata:progress:lastposition',
            'watchedsegments' => 'privacy:metadata:progress:watchedsegments',
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
                                              AND a.userid = :auserid
             WHERE ctx.contextlevel = :contextlevel
               AND m.name = :modname
               AND (p.id IS NOT NULL OR a.id IS NOT NULL)";
        $contextlist->add_from_sql($sql,
            ['puserid' => $userid, 'auserid' => $userid, 'contextlevel' => CONTEXT_MODULE, 'modname' => 'videoevidence']);
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
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videoevidence', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $userid = $contextlist->get_user()->id;
            $progress = $DB->get_record('videoevidence_progress', ['videoevidenceid' => $cm->instance, 'userid' => $userid]);
            $data = (object)['progress' => $progress ?: null];
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
                [$sql, $params] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'q');
                $params['userid'] = $userid;
                $answerids = $DB->get_fieldset_select('videoevidence_answers', 'id',
                    "questionid {$sql} AND userid = :userid", $params);
                if ($answerids) {
                    [$asql, $aparams] = $DB->get_in_or_equal($answerids, SQL_PARAMS_NAMED, 'a');
                    $DB->delete_records_select('videoevidence_evidence', "answerid {$asql}", $aparams);
                    $DB->delete_records_select('videoevidence_answers', "id {$asql}", $aparams);
                }
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
        $sql = "
            SELECT p.userid
              FROM {course_modules}         cm
              JOIN {videoevidence_progress}  p ON p.videoevidenceid = cm.instance
             WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
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
                $qparams['userid'] = $userid;
                $answerids = $DB->get_fieldset_select('videoevidence_answers', 'id',
                    "questionid {$qsql} AND userid = :userid", $qparams);
                if ($answerids) {
                    [$asql, $aparams] = $DB->get_in_or_equal($answerids, SQL_PARAMS_NAMED, 'a');
                    $DB->delete_records_select('videoevidence_evidence', "answerid {$asql}", $aparams);
                    $DB->delete_records_select('videoevidence_answers', "id {$asql}", $aparams);
                }
            }
            $DB->delete_records('videoevidence_progress', ['videoevidenceid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
