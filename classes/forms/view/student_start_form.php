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

namespace mod_activequiz\forms\view;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * Student start form to display the group to use if they have more than one
 * to start their session attempt
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_start_form extends \moodleform {

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
     * Form definition
     *
     */
    function definition() {
        global $USER;

        $custdata = $this->_customdata;
        $mform = $this->_form;
        /** @var \mod_activequiz\activequiz $rtq */
        $rtq = $custdata['rtq'];
        $validgroups = $custdata['validgroups'];

        // check if we're in group mode
        if ($rtq->group_mode()) {

            // get user's groups
            $groups = $rtq->get_groupmanager()->get_user_groups_name_array(null, false);

            // take out the invalid groups
            foreach ($groups as $key => $group) {
                if (!in_array($key, $validgroups)) {
                    unset($groups[ $key ]);
                }
            }

            if (count($groups) <= 1) { // first one will always be the '' index for the choose dots string
                // if only 1 group unset the choosedots index and disable the form element
                unset($groups['']);

                // add hidden element for group so that it's still passed on form submit
                reset($groups);
                $groupid = key($groups);
                $groupname = current($groups);

                $mform->addElement('hidden', 'group', $groupid);
                $mform->setType('group', PARAM_INT);

                $mform->addElement('static', 'group_text', get_string('group'), $groupname);
            } else {
                // add the choose dots string to the begining of the array for selecting
                $groups = array('' => get_string('choosedots')) + $groups;
                $mform->addElement('select', 'group', get_string('select_group', 'mod_activequiz'), $groups);
                $mform->setType('group', PARAM_INT);
            }

        }
        $mform->addElement('hidden', 'groupmode', $rtq->getRTQ()->workedingroups);
        $mform->setType('groupmode', PARAM_INT);

        if ($rtq->group_mode() && $rtq->getRTQ()->groupattendance == 1) {
            $mform->addElement('submit', 'submitbutton', get_string('continue'));
        } else {
            $mform->addElement('submit', 'submitbutton', get_string('joinquiz', 'mod_activequiz'));
        }

    }


    /**
     * Validate student input
     *
     * @param array $data
     * @param array $files
     *
     * @return array $errors
     */
    public function validation($data, $files) {
        global $USER;

        $errors = array();

        // only check when we're in group mode
        if ($data['groupmode'] == 1) {

            if (!isset($data['group'])) {
                if (isset($data['hidden_group'])) {
                    $data['group'] = $data['hidden_group'];
                } else {
                    $errors['group'] = get_string('notingroup', 'mod_activequiz');
                }
            }


            // make sure the user is in the group selected (shouldn't happen)
            if (!groups_is_member($data['group'], $USER->id)) {
                $errors['group'] = get_string('notingroup', 'mod_activequiz');
            }

        }

        return $errors;
    }
}


