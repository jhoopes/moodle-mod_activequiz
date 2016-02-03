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

namespace mod_activequiz\forms\edit;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Moodle form for confirming question add and get the time for the question
 * to appear on the page
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_question_form extends \moodleform {

    /**
     * Overriding parent function to account for namespace in the class name
     * so that client validation works
     *
     * @return mixed|string
     */
    protected function get_form_identifier() {

        $class = get_class($this);

        return preg_replace('/[^a-z0-9_]/i', '_', $class);
    }


    /**
     * Adds form fields to the form
     *
     */
    public function definition() {

        $mform = $this->_form;
        $rtq = $this->_customdata['rtq'];;
        $defaultTime = $rtq->getRTQ()->defaultquestiontime;

        $mform->addElement('static', 'questionid', get_string('question', 'activequiz'), $this->_customdata['questionname']);

        $mform->addElement('advcheckbox', 'notime', get_string('notime', 'activequiz'));
        $mform->setType('notime', PARAM_INT);
        $mform->addHelpButton('notime', 'notime', 'activequiz');
        $mform->setDefault('notime', 0);

        $mform->addElement('duration', 'indvquestiontime', get_string('indvquestiontime', 'activequiz'));
        $mform->disabledIf('indvquestiontime', 'notime', 'checked');
        $mform->setType('indvquestiontime', PARAM_INT);
        $mform->setDefault('indvquestiontime', $defaultTime);
        $mform->addHelpButton('indvquestiontime', 'indvquestiontime', 'activequiz');

        $mform->addElement('text', 'numberoftries', get_string('numberoftries', 'activequiz'));
        $mform->addRule('numberoftries', get_string('invalid_numberoftries', 'activequiz'), 'required', null, 'client');
        $mform->addRule('numberoftries', get_string('invalid_numberoftries', 'activequiz'), 'numeric', null, 'client');
        $mform->setType('numberoftries', PARAM_INT);
        $mform->setDefault('numberoftries', 1);
        $mform->addHelpButton('numberoftries', 'numberoftries', 'activequiz');

        $mform->addElement('text', 'points', get_string('points', 'activequiz'));
        $mform->addRule('points', get_string('invalid_points', 'activequiz'), 'required', null, 'client');
        $mform->addRule('points', get_string('invalid_points', 'activequiz'), 'numeric', null, 'client');
        $mform->setType('points', PARAM_FLOAT);
        $mform->setDefault('points', number_format($this->_customdata['defaultmark'], 2));
        $mform->addHelpButton('points', 'points', 'activequiz');

        $mform->addElement('advcheckbox', 'showhistoryduringquiz', get_string('showhistoryduringquiz', 'activequiz'));
        $mform->setType('showhistoryduringquiz', PARAM_INT);
        $mform->addHelpButton('showhistoryduringquiz', 'showhistoryduringquiz', 'activequiz');
        $mform->setDefault('showhistoryduringquiz', $this->_customdata['showhistoryduringquiz']);

        if (!empty($this->_customdata['edit'])) {
            $savestring = get_string('savequestion', 'activequiz');
        } else {
            $savestring = get_string('addquestion', 'activequiz');
        }

        $this->add_action_buttons(true, $savestring);

    }

    /**
     * Validate indv question time as int
     *
     * @param array $data
     * @param array $files
     *
     * @return array $errors
     */
    public function validation($data, $files) {

        $errors = array();

        if (!filter_var($data['indvquestiontime'], FILTER_VALIDATE_INT) && $data['indvquestiontime'] !== 0) {
            $errors['indvquestiontime'] = get_string('invalid_indvquestiontime', 'activequiz');
        } else if ($data['indvquestiontime'] < 0) {
            $errors['indvquestiontime'] = get_string('invalid_indvquestiontime', 'activequiz');
        }

        if (!filter_var($data['numberoftries'], FILTER_VALIDATE_INT) && $data['numberoftries'] !== 0) {
            $errors['numberoftries'] = get_string('invalid_numberoftries', 'activequiz');
        } else if ($data['numberoftries'] < 1) {
            $errors['numberoftries'] = get_string('invalid_numberoftries', 'activequiz');
        }

        if (!filter_var($data['points'], FILTER_VALIDATE_FLOAT) && filter_var($data['points'], FILTER_VALIDATE_FLOAT) != 0) {
            $errors['points'] = get_string('invalid_points', 'activequiz');
        } else if (filter_var($data['points'], FILTER_VALIDATE_FLOAT) < 0) {
            $errors['points'] = get_string('invalid_points', 'activequiz');
        }

        return $errors;
    }

}

