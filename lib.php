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
 * lib.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declares Moodle features supported by Video Evidence.
 *
 * @param string $feature Moodle feature constant.
 * @return bool|int|null
 */
function videoevidence_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Add an activity instance.
 */
function videoevidence_add_instance(stdClass $data, ?mod_videoevidence_mod_form $mform = null): int {
    global $DB;
    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    $id = $DB->insert_record('videoevidence', $data);
    $data->id = $id;
    videoevidence_save_files($data);
    videoevidence_grade_item_update($data);
    return $id;
}

/**
 * Update an activity instance.
 */
function videoevidence_update_instance(stdClass $data, ?mod_videoevidence_mod_form $mform = null): bool {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    $result = $DB->update_record('videoevidence', $data);
    videoevidence_save_files($data);
    videoevidence_grade_item_update($data);
    return $result;
}

/**
 * Delete an activity instance.
 */
function videoevidence_delete_instance(int $id): bool {
    global $DB;
    $activity = $DB->get_record('videoevidence', ['id' => $id]);
    if (!$activity) {
        return false;
    }
    $questionids = $DB->get_fieldset_select('videoevidence_questions', 'id', 'videoevidenceid = :id', ['id' => $id]);
    if ($questionids) {
        [$qsql, $qparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'q');
        $answerids = $DB->get_fieldset_select('videoevidence_answers', 'id', "questionid {$qsql}", $qparams);
        if ($answerids) {
            [$asql, $aparams] = $DB->get_in_or_equal($answerids, SQL_PARAMS_NAMED, 'a');
            $DB->delete_records_select('videoevidence_evidence', "answerid {$asql}", $aparams);
            $DB->delete_records_select('videoevidence_answers', "id {$asql}", $aparams);
        }
        $DB->delete_records_select('videoevidence_questions', "id {$qsql}", $qparams);
    }
    $DB->delete_records('videoevidence_progress', ['videoevidenceid' => $id]);
    if ($cm = get_coursemodule_from_instance('videoevidence', $id, $activity->course, false, IGNORE_MISSING)) {
        $context = context_module::instance($cm->id);
        get_file_storage()->delete_area_files($context->id, 'mod_videoevidence');
    }
    $DB->delete_records('videoevidence', ['id' => $id]);
    videoevidence_grade_item_delete($activity);
    return true;
}

/**
 * Save protected video and poster files from draft areas.
 */
function videoevidence_save_files(stdClass $activity): void {
    if (empty($activity->coursemodule)) {
        $cm = get_coursemodule_from_instance('videoevidence', $activity->id, $activity->course, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $cmid = $cm->id;
    } else {
        $cmid = $activity->coursemodule;
    }
    $context = context_module::instance($cmid);
    if (isset($activity->videofile)) {
        file_save_draft_area_files($activity->videofile, $context->id, 'mod_videoevidence', 'video', 0,
            ['subdirs' => 0, 'maxfiles' => 1]);
    }
    if (isset($activity->poster)) {
        file_save_draft_area_files($activity->poster, $context->id, 'mod_videoevidence', 'poster', 0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]);
    }
}

/**
 * Serve protected video/poster files.
 */
function mod_videoevidence_pluginfile($course, $cm, $context, string $filearea, array $args,
                                      bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, ['video', 'poster'], true)) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videoevidence:view', $context);
    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_videoevidence', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Grade item update.
 */
function videoevidence_grade_item_update(stdClass $activity, array|null $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $item = [
        'itemname' => clean_param($activity->name, PARAM_NOTAGS),
        'gradetype' => ((float)$activity->grade > 0) ? GRADE_TYPE_VALUE : GRADE_TYPE_NONE,
        'grademin' => 0,
        'grademax' => max(0, (float)$activity->grade),
    ];
    return grade_update('mod/videoevidence', $activity->course, 'mod', 'videoevidence', $activity->id, 0, $grades, $item);
}

/**
 * Publish one or all grades.
 */
function videoevidence_update_grades(stdClass $activity, int $userid = 0, bool $nullifnone = true): void {
    global $DB;
    $userids = [];
    if ($userid) {
        $userids = [$userid];
    } else {
        $sql = "SELECT DISTINCT a.userid
                  FROM {videoevidence_answers} a
                  JOIN {videoevidence_questions} q ON q.id = a.questionid
                 WHERE q.videoevidenceid = :activityid";
        $userids = $DB->get_fieldset_sql($sql, ['activityid' => $activity->id]);
    }
    $grades = [];
    foreach ($userids as $uid) {
        $percent = \mod_videoevidence\grading_manager::activity_score($activity->id, (int)$uid);
        $grades[(int)$uid] = (object)[
            'userid' => (int)$uid,
            'rawgrade' => $percent === null ? null : ((float)$activity->grade * $percent / 100),
        ];
    }
    if (!$grades && $userid && $nullifnone) {
        $grades[$userid] = (object)['userid' => $userid, 'rawgrade' => null];
    }
    videoevidence_grade_item_update($activity, $grades);
}

/**
 * Delete grade item.
 */
function videoevidence_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/videoevidence', $activity->course, 'mod', 'videoevidence', $activity->id, 0, null, ['deleted' => 1]);
}

/**
 * Course module cache data.
 */
function videoevidence_get_coursemodule_info(stdClass $cm): cached_cm_info|null {
    global $DB;
    $activity = $DB->get_record('videoevidence', ['id' => $cm->instance], 'id,name,intro,introformat,completionevidence');
    if (!$activity) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videoevidence', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = ['completionevidence' => (bool)$activity->completionevidence];
    }
    return $info;
}

/**
 * Describes active completion rule.
 */
function videoevidence_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC ||
        empty($cm->customdata['customcompletionrules']['completionevidence'])) {
        return [];
    }
    return [get_string('completiondetail:evidence', 'videoevidence')];
}

/**
 * Legacy completion callback.
 */
function videoevidence_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;
    $activity = $DB->get_record('videoevidence', ['id' => $cm->instance], '*', MUST_EXIST);
    if (!$activity->completionevidence) {
        return $type;
    }
    return \mod_videoevidence\grading_manager::required_evidence_complete($activity->id, $userid);
}
