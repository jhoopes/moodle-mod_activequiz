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
 * Defines backup_activequiz_activity_structure_step class
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @author      Davo Smith
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_activequiz_activity_task
 */

/**
 * Define the complete activequiz structure for backup, with file and id annotations
 */
class backup_activequiz_activity_structure_step extends backup_questions_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.

        $activequiz = new backup_nested_element('activequiz', array('id'), array(
            'name', 'intro', 'introformat', 'graded', 'scale', 'grademethod', 'workedingroups',
            'grouping', 'groupattendance', 'reviewoptions', 'timecreated', 'timemodified', 'defaultquestiontime',
            'waitforquestiontime', 'questionorder'
        ));

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', array('id'), array(
            'questionid', 'notime', 'questiontime', 'tries', 'points', 'showhistoryduringquiz'
        ));

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'), array(
            'userid', 'gradeval', 'timemodified'
        ));

        $sessions = new backup_nested_element('sessions');
        $session = new backup_nested_element('session', array('id'), array(
            'name', 'anonymize_responses', 'fully_anonymize', 'sessionopen', 'status', 'currentquestion',
            'currentqnum', 'currentquestiontime', 'classresult', 'nextstarttime', 'created'
        ));

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'), array(
            'userid', 'attemptnum', 'questionengid', 'status', 'preview', 'responded', 'forgroupid',
            'timestart', 'timefinish', 'timemodified', 'qubalayout'
        ));

        // This module is using questions, so produce the related question states and sessions
        // attaching them to the $attempt element based in 'questionengid' matching.
        $this->add_question_usages($attempt, 'questionengid');

        $groupattendances = new backup_nested_element('groupattendances');
        $groupattendance = new backup_nested_element('groupattendance', array('id'), array(
            'activequizid', 'sessionid', 'groupid', 'userid'
        ));

        // Build the tree.
        $activequiz->add_child($questions);
        $questions->add_child($question);

        $activequiz->add_child($grades);
        $grades->add_child($grade);

        $activequiz->add_child($sessions);
        $sessions->add_child($session);

        $session->add_child($attempts);
        $attempts->add_child($attempt);

        $attempt->add_child($groupattendances);
        $groupattendances->add_child($groupattendance);

        // Define sources.
        $activequiz->set_source_table('activequiz', array('id' => backup::VAR_ACTIVITYID));
        $question->set_source_table('activequiz_questions', array('activequizid' => backup::VAR_PARENTID));

        // If user info backup grades table.
        if ($userinfo) {
            $grade->set_source_table('activequiz_grades', array('activequizid' => backup::VAR_PARENTID));
            $session->set_source_table('activequiz_sessions', array('activequizid' => backup::VAR_PARENTID));
            $attempt->set_source_table('activequiz_attempts', array('sessionid' => backup::VAR_PARENTID));
            $groupattendance->set_source_table('activequiz_groupattendance', array('attemptid' => backup::VAR_PARENTID));
        }

        // Define source alias.
        $grade->set_source_alias('grade', 'gradeval');

        // Define id annotations.
        $activequiz->annotate_ids('grouping', 'grouping');
        $grade->annotate_ids('user', 'userid');
        $attempt->annotate_ids('user', 'userid');
        $attempt->annotate_ids('group', 'forgroupid');
        $question->annotate_ids('question', 'questionid');
        $groupattendance->annotate_ids('group', 'groupid');
        $groupattendance->annotate_ids('user', 'userid');

        // Define file annotations.
        $activequiz->annotate_files('mod_activequiz', 'intro', null); // This file area hasn't itemid.

        // Return the root element (activequiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($activequiz);
    }

}
