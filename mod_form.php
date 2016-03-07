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
 * The main configuration form
 *
 * @package   mod_activequiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @author    Davo Smith
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_activequiz_mod_form extends moodleform_mod {


    public function __construct($current, $section, $cm, $course) {

        parent::__construct($current, $section, $cm, $course);
    }


    function definition() {

        global $COURSE;
        $mform =& $this->_form;

        //-------------------------------------------------------------------------------
        /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description'));

        //-------------------------------------------------------------------------------

        $mform->addElement('header', 'activequizsettings', get_string('activequizsettings', 'activequiz'));

        $mform->addElement('duration', 'defaultquestiontime', get_string('defaultquestiontime', 'activequiz'));
        $mform->setDefault('defaultquestiontime', 30);
        $mform->setType('defaultquestiontime', PARAM_INT);
        $mform->addHelpButton('defaultquestiontime', 'defaultquestiontime', 'activequiz');

        $mform->addElement('duration', 'waitforquestiontime', get_string('waitforquestiontime', 'activequiz'));
        $mform->setDefault('waitforquestiontime', 5);
        $mform->setType('waitforquestiontime', PARAM_INT);
        $mform->addHelpButton('waitforquestiontime', 'waitforquestiontime', 'activequiz');

        /*$mform->addElement('checkbox', 'anonymizeresponses', get_string('anonymousresponses', 'activequiz'));
        $mform->addHelpButton('anonymizeresponses', 'anonymousresponses', 'activequiz');
        $mform->setDefault('anonymizeresponses', 0); */

        $mform->addElement('header', 'gradesettings', get_string('gradesettings', 'activequiz'));

        $mform->addElement('checkbox', 'graded', get_string('assessed', 'activequiz'));
        $mform->addHelpButton('graded', 'assessed', 'activequiz');
        $mform->setDefault('graded', 1);

        $mform->addElement('text', 'scale', get_string('scale', 'activequiz'));
        $mform->addRule('scale', null, 'numeric', null, 'client');
        $mform->disabledIf('scale', 'graded');
        $mform->setDefault('scale', 10);
        $mform->setType('scale', PARAM_INT);
        $mform->addHelpButton('scale', 'scale', 'activequiz');

        $mform->addElement('select', 'grademethod',
            get_string('grademethod', 'activequiz'),
            \mod_activequiz\utils\scaletypes::get_display_types());
        $mform->setType('grademethod', PARAM_INT);
        $mform->addHelpButton('grademethod', 'grademethod', 'activequiz');


        // check if there are any sessions on this realtime quiz
        $changegroups = true;
        if (!empty($this->_instance)) {
            global $DB;
            $sessions = $DB->get_records('activequiz_sessions', array('activequizid' => $this->_instance));
            if (!empty($sessions)) {
                $changegroups = false;
            }
        }

        // group settings
        $mform->addElement('header', 'groupsettings', get_string('groupworksettings', 'activequiz'));

        $coursegroupings = $this->get_groupings();

        if ($changegroups == false || empty($coursegroupings)) {
            $mform->addElement('static', 'nogroups', get_string('nochangegroups_label', 'activequiz'), get_string('nochangegroups', 'activequiz'));
        }

        $mform->addElement('advcheckbox', 'workedingroups', get_string('workedingroups', 'activequiz'));
        $mform->addHelpButton('workedingroups', 'workedingroups', 'activequiz');
        $mform->setDefault('workedingroups', 0);


        $mform->addElement('select', 'grouping', get_string('grouping', 'activequiz'), $coursegroupings);
        $mform->disabledIf('grouping', 'workedingroups');
        $mform->setType('grouping', PARAM_INT);
        $mform->addHelpButton('grouping', 'grouping', 'activequiz');

        $mform->addElement('advcheckbox', 'groupattendance', get_string('groupattendance', 'activequiz'));
        $mform->addHelpButton('groupattendance', 'groupattendance', 'activequiz');
        $mform->disabledIf('groupattendance', 'workedingroups');
        $mform->setDefault('groupattendance', 0);

        if ($changegroups == false || empty($coursegroupings)) {
            $mform->freeze('workedingroups');
            $mform->freeze('grouping');
            $mform->freeze('groupattendance');
        }

        // review option settings
        $mform->addElement('header', 'reviewoptionsettings', get_string('reviewoptionsettings', 'activequiz'));

        $this->add_review_options_group($mform, 'after', true);


        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    /**
     * Gets a grouping array for display
     *
     * @return array
     */
    protected function get_groupings() {

        // get the courseid from the module's course context
        if (get_class($this->context) == 'context_course') {
            // if the context defined for the form is a context course just get its id
            $courseid = $this->context->instanceid;
        } else {
            $cmcontext = context_module::instance($this->_cm->id);
            $coursecontext = $cmcontext->get_course_context(false);
            $courseid = $coursecontext->instanceid;
        }

        $groupings = groups_get_all_groupings($courseid);

        // create an array with just the grouping id and name
        $retgroupings = array();
        $retgroupings[''] = get_string('none');
        foreach ($groupings as $grouping) {
            $retgroupings[ $grouping->id ] = $grouping->name;
        }


        return $retgroupings;
    }

    /**
     * Adapted from the quiz module's review options group function
     *
     * @param      $mform
     * @param      $whenname
     * @param bool $withhelp
     */
    protected function add_review_options_group($mform, $whenname,
                                                $withhelp = false) {
        global $OUTPUT;

        /** @var MoodleQuickForm $mform */

        $group = array();
        foreach (\mod_activequiz\activequiz::$reviewfields as $field => $string) {
            list($identifier, $component) = $string;

            $label = get_string($identifier, $component);
            if ($withhelp) {
                $label .= ' ' . $OUTPUT->help_icon($identifier, $component);
            }

            $group[] = $mform->createElement('advcheckbox', $field, '', $label);
        }
        $mform->addGroup($group, $whenname . 'optionsgrp',
            get_string('review' . $whenname, 'activequiz'), null, true);

        foreach (\mod_activequiz\activequiz::$reviewfields as $field => $notused) {
            $mform->setDefault($whenname . 'optionsgrp[' . $field . ']', 1);
            if ($whenname != 'during' && $field != 'attempt') {
                $mform->disabledIf($whenname . 'optionsgrp[' . $field . ']', $whenname . 'optionsgrp[attempt]');
            }
        }

    }
}
