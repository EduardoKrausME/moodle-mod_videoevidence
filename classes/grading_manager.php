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
 * grading_manager.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence;

use stdClass;

/**
 * Class grading_manager.
 */
class grading_manager {
    /**
     * Method answer.
     *
     * @param stdClass $question Parameter question.
     * @param int $userid Parameter userid.
     * @return stdClass Return value.
     */
    public static function answer(stdClass $question, int $userid): stdClass {
        global $DB;
        $answer = $DB->get_record('videoevidence_answers', ['questionid' => $question->id, 'userid' => $userid]);
        if (!$answer) {
            $now = time();
            $answer = (object)[
                'questionid' => $question->id, 'userid' => $userid, 'status' => 'draft',
                'selectionscore' => null, 'justificationscore' => null, 'quantityscore' => null,
                'finalscore' => null, 'feedback' => null, 'timesubmitted' => 0, 'timegraded' => 0,
                'grader' => 0, 'timecreated' => $now, 'timemodified' => $now,
            ];
            $answer->id = $DB->insert_record('videoevidence_answers', $answer);
        }
        return $answer;
    }

    /**
     * Method reset_to_draft.
     *
     * @param stdClass $answer Parameter answer.
     * @return void Return value.
     */
    public static function reset_to_draft(stdClass $answer): void {
        global $DB;
        if ($answer->status === 'graded') {
            return;
        }
        $answer->status = 'draft';
        $answer->selectionscore = null;
        $answer->quantityscore = null;
        $answer->justificationscore = null;
        $answer->finalscore = null;
        $answer->timesubmitted = 0;
        $answer->timemodified = time();
        $DB->update_record('videoevidence_answers', $answer);
    }

    /**
     * Method submit.
     *
     * @param stdClass $question Parameter question.
     * @param stdClass $answer Parameter answer.
     * @return stdClass Return value.
     */
    public static function submit(stdClass $question, stdClass $answer): stdClass {
        global $DB;
        $evidence = array_values($DB->get_records('videoevidence_evidence', ['answerid' => $answer->id], 'sortorder,id'));
        if (count($evidence) < (int)$question->minevidence) {
            throw new \moodle_exception('notenoughevidence', 'videoevidence');
        }
        $answer->quantityscore = count($evidence) >= (int)$question->minevidence ? 100.0 : 0.0;
        if ($question->expectedstart !== null && $question->expectedend !== null) {
            $best = 0.0;
            foreach ($evidence as $item) {
                $best = max($best, self::selection_match($item, $question));
            }
            $answer->selectionscore = $best;
        } else {
            $answer->selectionscore = null;
        }
        if ((float)$question->justificationweight <= 0) {
            $answer->justificationscore = 100.0;
        }
        $answer->status = 'submitted';
        $answer->timesubmitted = time();
        $answer->timemodified = time();
        self::recalculate($question, $answer);
        $DB->update_record('videoevidence_answers', $answer);
        return $answer;
    }

    /**
     * Method selection_match.
     *
     * @param stdClass $evidence Parameter evidence.
     * @param stdClass $question Parameter question.
     * @return float Return value.
     */
    public static function selection_match(stdClass $evidence, stdClass $question): float {
        $expectedstart = max(0, (float)$question->expectedstart - (float)$question->tolerance);
        $expectedend = max($expectedstart, (float)$question->expectedend + (float)$question->tolerance);
        $start = (float)$evidence->starttime;
        $end = max($start, (float)$evidence->endtime);
        if ($question->selectiontype === 'moment') {
            return ($start >= $expectedstart && $start <= $expectedend) ? 100.0 : 0.0;
        }
        $intersection = max(0, min($end, $expectedend) - max($start, $expectedstart));
        $union = max($end, $expectedend) - min($start, $expectedstart);
        return $union > 0 ? min(100, 100 * $intersection / $union) : 0.0;
    }

    /**
     * Method recalculate.
     *
     * @param stdClass $question Parameter question.
     * @param stdClass $answer Parameter answer.
     * @return void Return value.
     */
    public static function recalculate(stdClass $question, stdClass $answer): void {
        $parts = [
            ['weight' => (float)$question->selectionweight, 'score' => $answer->selectionscore],
            ['weight' => (float)$question->justificationweight, 'score' => $answer->justificationscore],
            ['weight' => (float)$question->quantityweight, 'score' => $answer->quantityscore],
        ];
        $totalweight = 0.0;
        $points = 0.0;
        foreach ($parts as $part) {
            if ($part['weight'] <= 0) {
                continue;
            }
            if ($part['score'] === null) {
                $answer->finalscore = null;
                return;
            }
            $totalweight += $part['weight'];
            $points += $part['weight'] * (float)$part['score'] / 100;
        }
        $answer->finalscore = $totalweight > 0 ? min(100, 100 * $points / $totalweight) : 100.0;
    }

    /**
     * Method activity_score.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return ?float Return value.
     */
    public static function activity_score(int $activityid, int $userid): ?float {
        global $DB;
        $sql = "SELECT q.id, a.finalscore
                  FROM {videoevidence_questions} q
             LEFT JOIN {videoevidence_answers} a ON a.questionid = q.id AND a.userid = :userid
                 WHERE q.videoevidenceid = :activityid";
        $rows = $DB->get_records_sql($sql, ['userid' => $userid, 'activityid' => $activityid]);
        if (!$rows) {
            return null;
        }
        $sum = 0.0;
        foreach ($rows as $row) {
            if ($row->finalscore === null) {
                return null;
            }
            $sum += (float)$row->finalscore;
        }
        return $sum / count($rows);
    }

    /**
     * Method required_evidence_complete.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return bool Return value.
     */
    public static function required_evidence_complete(int $activityid, int $userid): bool {
        global $DB;
        $questions = $DB->get_records('videoevidence_questions', ['videoevidenceid' => $activityid, 'required' => 1]);
        foreach ($questions as $question) {
            $answer = $DB->get_record('videoevidence_answers', ['questionid' => $question->id, 'userid' => $userid]);
            if (!$answer || !in_array($answer->status, ['submitted', 'graded'], true)) {
                return false;
            }
            if ($DB->count_records('videoevidence_evidence', ['answerid' => $answer->id]) < (int)$question->minevidence) {
                return false;
            }
        }
        return true;
    }

    /**
     * Method sync.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return void Return value.
     */
    public static function sync(int $activityid, int $userid): void {
        global $DB;
        $activity = $DB->get_record('videoevidence', ['id' => $activityid], '*', MUST_EXIST);
        videoevidence_update_grades($activity, $userid);
        $cm = get_coursemodule_from_instance('videoevidence', $activityid, $activity->course, false, IGNORE_MISSING);
        if ($cm) {
            $course = $DB->get_record('course', ['id' => $activity->course], '*', MUST_EXIST);
            $completion = new \completion_info($course);
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
        }
    }
}
