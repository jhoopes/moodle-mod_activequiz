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
 *
 *
 * @package   mod_activequiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groupselectmembers extends \moodleform {


    /**
     * Defines form definition
     *
     */
    public function definition() {
        global $USER;
        $custdata = $this->_customdata;
        $mform = $this->_form;
        /** @var \mod_activequiz\activequiz $rtq */
        $rtq = $custdata['rtq'];
        $selectedgroup = $custdata['selectedgroup'];

        $groupmembers = $rtq->get_groupmanager()->get_group_members($selectedgroup);

        $groupmemnum = 1;
        foreach ($groupmembers as $groupmember) {

            $attributes = array('group' => 1);


            $mform->addElement('advcheckbox', 'gm' . $groupmemnum, null,
                fullname($groupmember), $attributes,
                array(0, $groupmember->id));


            $groupmemnum++;

        }
        $this->add_checkbox_controller(1);

        $mform->addElement('submit', 'submitbutton', get_string('joinquiz', 'mod_activequiz'));

    }
}


