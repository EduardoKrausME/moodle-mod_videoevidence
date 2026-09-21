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
 * restore_videoevidence_stepslib.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class restore_videoevidence_activity_structure_step.
 */
class restore_videoevidence_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('videoevidence', '/activity/videoevidence'),
            new restore_path_element('videoevidence_question', '/activity/videoevidence/questions/question'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videoevidence_answer',
                '/activity/videoevidence/questions/question/answers/answer');
            $paths[] = new restore_path_element('videoevidence_evidence',
                '/activity/videoevidence/questions/question/answers/answer/evidences/evidence');
            $paths[] = new restore_path_element('videoevidence_progress',
                '/activity/videoevidence/progresses/progress');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Method process_videoevidence.
     *
     * @param array $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoevidence(array $data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->id = $DB->insert_record('videoevidence', $data);
        $this->apply_activity_instance($data->id);
        $this->set_mapping('videoevidence', $oldid, $data->id, true);
    }

    /**
     * Method process_videoevidence_question.
     *
     * @param array $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoevidence_question(array $data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videoevidenceid = $this->get_new_parentid('videoevidence');
        $data->id = $DB->insert_record('videoevidence_questions', $data);
        $this->set_mapping('videoevidence_question', $oldid, $data->id);
    }

    /**
     * Method process_videoevidence_answer.
     *
     * @param array $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoevidence_answer(array $data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->questionid = $this->get_new_parentid('videoevidence_question');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        $data->grader = $this->get_mappingid('user', $data->grader, 0);
        if (!$data->userid) {
            return;
        }
        $data->id = $DB->insert_record('videoevidence_answers', $data);
        $this->set_mapping('videoevidence_answer', $oldid, $data->id);
    }

    /**
     * Method process_videoevidence_evidence.
     *
     * @param array $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoevidence_evidence(array $data): void {
        global $DB;
        $data = (object)$data;
        $data->answerid = $this->get_new_parentid('videoevidence_answer');
        $DB->insert_record('videoevidence_evidence', $data);
    }

    /**
     * Method process_videoevidence_progress.
     *
     * @param array $data Parameter data.
     * @return void Return value.
     */
    protected function process_videoevidence_progress(array $data): void {
        global $DB;
        $data = (object)$data;
        $data->videoevidenceid = $this->get_new_parentid('videoevidence');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if ($data->userid) {
            $DB->insert_record('videoevidence_progress', $data);
        }
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videoevidence', 'video', null);
        $this->add_related_files('mod_videoevidence', 'poster', null);
    }
}
