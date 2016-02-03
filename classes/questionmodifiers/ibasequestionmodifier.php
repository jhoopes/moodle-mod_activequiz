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

namespace mod_activequiz\questionmodifiers;

defined('MOODLE_INTERNAL') || die();

/**
 * The controller for handling quiz data callbacks from javascript
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface ibasequestionmodifier {


    /**
     * Method to determine whether or not your question modifier needs jQuery.  This will be called on page header for quiz rendering
     *
     * If your question modifer does need jquery, require it
     */
    public function requires_jquery();

    /**
     * Add CSS to the page.  This is called when the quiz renders to the page
     */
    public function add_css();

    /**
     * Method to allow the question modifier to add js to the page (note this should not include jQuery
     */
    public function add_js();

    /**
     * Allows a question modifier to change the output of a particular question when displaying
     * results to the instructor
     *
     * @param \mod_activequiz\activequiz_question $question The realtime quiz question that we're currently on
     * @param array                               $attempts An array of \mod_activequiz\activequiz_attempt classes
     * @param string                              $output The current output from getting the results
     * @return string Return the updated output to be passed to the client
     */
    public function modify_questionresults_duringquiz($question, $attempts, $output);


}

