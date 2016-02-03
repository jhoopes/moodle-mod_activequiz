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

require_once($CFG->dirroot . '/question/engine/lib.php');

/**
 * Poodll Recording question modifier class
 *
 * @package   mod_activequiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2015 University of Wisconsin - madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poodllrecording implements \mod_activequiz\questionmodifiers\ibasequestionmodifier {


    public function requires_jquery() {
    }

    public function add_css() {
        global $PAGE;
        $PAGE->requires->css('/mod/activequiz/js/questionmodifiers/poodllrecording/lightbox/css/lightbox.css');
        $PAGE->requires->css('/mod/activequiz/js/questionmodifiers/poodllrecording/styles.css');
    }

    /**
     * Add js and css
     *
     */
    public function add_js() {
        global $PAGE;
        $PAGE->requires->js('/mod/activequiz/js/questionmodifiers/poodllrecording/lightbox_images.js');
        $PAGE->requires->js('/mod/activequiz/js/questionmodifiers/poodllrecording/lightbox/lightbox.min.js');

    }

    /**
     * Updating output to include javascript to initiate a lightbox effect on drawing type questions
     * so that the images are smaller unless clicked on for review
     *
     * @param \mod_activequiz\activequiz_question $question The realtime quiz question
     * @param array                               $attempts An array of \mod_activequiz\activequiz_attempt classes
     * @param string                              $output The current output from getting the results
     * @return string Return the updated output to be passed to the client
     */
    public function modify_questionresults_duringquiz($question, $attempts, $output) {
        global $DB;

        // if no attempts just return the output
        if (empty($attempts)) {
            return $output;
        }

        // get the question definition to determine response format
        reset($attempts);
        $attempt = current($attempts);
        /** @var \mod_activequiz\activequiz_attempt $attempt */
        $quba = $attempt->get_quba();
        $slot = $attempt->get_question_slot($question);
        $qa = $quba->get_question_attempt($slot);
        // now get question definition
        $questiondef = $qa->get_question();

        if ($questiondef->responseformat == 'picture') {

            // if the response format is a picture, meaning drawing type add the js for lightbox stuff

            $picturejs = \html_writer::start_tag('script', array('type' => 'text/javascript', 'id' => 'poodllrecording_js'));
            $picturejs .= '
                activequiz.questionmodifiers.poodllrecording.lightbox_images();
            ';
            $picturejs .= \html_writer::end_tag('script');

            $newoutput = $output . $picturejs;

            $newoutput = \html_writer::div($newoutput, '', array('id' => 'poodllrecording-picture'));

        } else {
            $newoutput = $output;
        }


        return $newoutput;
    }


}
