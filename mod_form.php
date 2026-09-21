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
 * mod_form.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoevidence\source_manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Class mod_videoevidence_mod_form.
 */
class mod_videoevidence_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videoevidencename', 'videoevidence'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'videoheader', get_string('videoheader', 'videoevidence'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videoevidence'), [
            'upload' => get_string('sourceupload', 'videoevidence'),
            'url' => get_string('sourceurl', 'videoevidence'),
            'youtube' => get_string('sourceyoutube', 'videoevidence'),
            'vimeo' => get_string('sourcevimeo', 'videoevidence'),
        ]);
        $mform->setDefault('videosource', 'upload');
        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videoevidence'), null, [
            'subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['video'],
        ]);
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');
        $mform->addElement('url', 'videourl', get_string('videourl', 'videoevidence'), ['size' => 80], ['usefilepicker' => false]);
        $mform->setType('videourl', PARAM_URL);
        $mform->hideIf('videourl', 'videosource', 'eq', 'upload');
        $mform->addElement('filemanager', 'poster', get_string('poster', 'videoevidence'), null, [
            'subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image'],
        ]);
        $mform->addElement('select', 'resumeplayback', get_string('resumeplayback', 'videoevidence'), [
            1 => get_string('resumeautomatic', 'videoevidence'),
            2 => get_string('resumeask', 'videoevidence'),
            0 => get_string('resumefromstart', 'videoevidence'),
        ]);
        $mform->setDefault('resumeplayback', 1);
        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'videoevidence'));
        $mform->setDefault('allowseek', 1);

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', 100);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $source = $data['videosource'] ?? 'upload';
        if ($source !== 'upload') {
            $url = trim((string)($data['videourl'] ?? ''));
            $extensions = ['mp4', 'webm', 'ogv', 'm4v', 'mov', 'm3u8'];
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['videourl'] = get_string('invalidvideourl', 'videoevidence');
            } else if ($source === 'url' &&
                !in_array(strtolower(pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)), $extensions, true)) {
                $errors['videourl'] = get_string('invaliddirecturl', 'videoevidence');
            } else if ($source === 'youtube' && !source_manager::youtube_id($url)) {
                $errors['videourl'] = get_string('invalidyoutubeurl', 'videoevidence');
            } else if ($source === 'vimeo' && !source_manager::vimeo_config($url)) {
                $errors['videourl'] = get_string('invalidvimeourl', 'videoevidence');
            }
        }
        return $errors;
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return void Return value.
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (array_key_exists('completionevidence', $defaultvalues)) {
            $defaultvalues['completionevidence_videoevidence'] = $defaultvalues['completionevidence'];
        }
        if (empty($this->current->instance)) {
            return;
        }
        $context = $this->context;
        foreach (['videofile' => 'video', 'poster' => 'poster'] as $field => $area) {
            $draftid = file_get_submitted_draft_itemid($field);
            file_prepare_draft_area($draftid, $context->id, 'mod_videoevidence', $area, 0, ['subdirs' => 0]);
            $defaultvalues[$field] = $draftid;
        }
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        $field = 'completionevidence_videoevidence';
        $this->_form->addElement('checkbox', $field, get_string('completionevidence', 'videoevidence'));
        $this->_form->setDefault($field, 1);
        return [$field];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionevidence_videoevidence']);
    }

    /**
     * Method get_data.
     *
     * @return mixed Return value.
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->completionevidence = !empty($data->completionevidence_videoevidence) ? 1 : 0;
            if (property_exists($data, 'completionevidence_videoevidence')) {
                unset($data->completionevidence_videoevidence);
            }
        }
        return $data;
    }
}
