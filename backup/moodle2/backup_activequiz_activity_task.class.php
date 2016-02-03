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
 * Defines backup_activequiz_activity_task class
 *
 * @package     mod_activequiz
 * @author      John Hoopes <smoodle@madisoncreativeweb.com>
 * @author      Davo Smith
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/activequiz/backup/moodle2/backup_activequiz_stepslib.php');
require_once($CFG->dirroot . '/mod/activequiz/backup/moodle2/backup_activequiz_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the Forum instance
 */
class backup_activequiz_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the activequiz.xml file
     */
    protected function define_my_steps() {
        // Generate the activequiz.xml file containing all information
        // and annotating used questions.
        $this->add_step(new backup_activequiz_activity_structure_step('activequiz_structure', 'activequiz.xml'));

        // Note: Following  steps must be present
        // in all the activities using question banks (only quiz for now)
        // TODO: Specialise these step to a new subclass of backup_activity_task.

        // Process all the annotated questions to calculate the question
        // categories needing to be included in backup for this activity
        // plus the categories belonging to the activity context itself.
        $this->add_step(new backup_calculate_question_categories('activity_question_categories'));

        // Clean backup_temp_ids table from questions. We already
        // have used them to detect question_categories and aren't
        // needed anymore.
        $this->add_step(new backup_delete_temp_questions('clean_temp_questions'));
    }

    /**
     * Encodes URLs to the index.php, view.php and discuss.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of activequizs.
        $search = "/(" . $base . "\/mod\/activequiz\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@ACTIVEQUIZINDEX*$2@$', $content);

        // Link to activequiz view by moduleid.
        $search = "/(" . $base . "\/mod\/activequiz\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@ACTIVEQUIZVIEWBYID*$2@$', $content);

        return $content;
    }
}
