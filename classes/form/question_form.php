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
 * question_form.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->libdir}/formslib.php");

/**
 * Class question_form.
 */
class question_form extends \moodleform {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('editor', 'questiontext_editor', get_string('questiontext', 'videoevidence'), null,
            ['maxfiles' => 0, 'trusttext' => false]);
        $mform->setType('questiontext_editor', PARAM_RAW);
        $mform->addRule('questiontext_editor', null, 'required', null, 'client');
        $mform->addElement('select', 'selectiontype', get_string('selectiontype', 'videoevidence'), [
            'moment' => get_string('selectionmoment', 'videoevidence'),
            'interval' => get_string('selectioninterval', 'videoevidence'),
        ]);
        $mform->addElement('text', 'minevidence', get_string('minevidence', 'videoevidence'), ['size' => 5]);
        $mform->setType('minevidence', PARAM_INT);
        $mform->setDefault('minevidence', 1);
        $mform->addElement('text', 'maxevidence', get_string('maxevidence', 'videoevidence'), ['size' => 5]);
        $mform->setType('maxevidence', PARAM_INT);
        $mform->setDefault('maxevidence', 1);
        $mform->addElement('text', 'expectedstarttext', get_string('expectedstart', 'videoevidence'), ['size' => 12]);
        $mform->setType('expectedstarttext', PARAM_TEXT);
        $mform->addElement('text', 'expectedendtext', get_string('expectedend', 'videoevidence'), ['size' => 12]);
        $mform->setType('expectedendtext', PARAM_TEXT);
        $mform->addElement('text', 'tolerance', get_string('tolerance', 'videoevidence'), ['size' => 8]);
        $mform->setType('tolerance', PARAM_FLOAT);
        $mform->setDefault('tolerance', 0);
        $mform->addElement('text', 'selectionweight', get_string('selectionweight', 'videoevidence'), ['size' => 5]);
        $mform->setType('selectionweight', PARAM_FLOAT);
        $mform->setDefault('selectionweight', 50);
        $mform->addElement('text', 'justificationweight', get_string('justificationweight', 'videoevidence'), ['size' => 5]);
        $mform->setType('justificationweight', PARAM_FLOAT);
        $mform->setDefault('justificationweight', 40);
        $mform->addElement('text', 'quantityweight', get_string('quantityweight', 'videoevidence'), ['size' => 5]);
        $mform->setType('quantityweight', PARAM_FLOAT);
        $mform->setDefault('quantityweight', 10);
        $mform->addElement('advcheckbox', 'required', get_string('requiredquestion', 'videoevidence'));
        $mform->setDefault('required', 1);
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
        $min = (int)($data['minevidence'] ?? 0);
        $max = (int)($data['maxevidence'] ?? 0);
        if ($min < 1) {
            $errors['minevidence'] = get_string('invalidminimum', 'videoevidence');
        }
        if ($max < $min) {
            $errors['maxevidence'] = get_string('invalidmaximum', 'videoevidence');
        }
        foreach (['selectionweight', 'justificationweight', 'quantityweight'] as $field) {
            $value = (float)($data[$field] ?? 0);
            if ($value < 0 || $value > 100) {
                $errors[$field] = get_string('invalidweight', 'videoevidence');
            }
        }
        if ((float)($data['tolerance'] ?? 0) < 0) {
            $errors['tolerance'] = get_string('invalidtolerance', 'videoevidence');
        }
        foreach (['expectedstarttext', 'expectedendtext'] as $field) {
            if (trim((string)($data[$field] ?? '')) !== '' && \mod_videoevidence\timecode::parse($data[$field]) === null) {
                $errors[$field] = get_string('invalidtimecode', 'videoevidence');
            }
        }
        $hasstart = trim((string)($data['expectedstarttext'] ?? '')) !== '';
        $hasend = trim((string)($data['expectedendtext'] ?? '')) !== '';
        if ($hasstart xor $hasend) {
            $errors[$hasstart ? 'expectedendtext' : 'expectedstarttext'] = get_string('expectedboth', 'videoevidence');
        }
        if ($hasstart && $hasend) {
            $start = \mod_videoevidence\timecode::parse($data['expectedstarttext']);
            $end = \mod_videoevidence\timecode::parse($data['expectedendtext']);
            if ($end < $start) {
                $errors['expectedendtext'] = get_string('endbeforestart', 'videoevidence');
            }
        }
        return $errors;
    }
}
