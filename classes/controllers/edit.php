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

namespace mod_activequiz\controllers;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * edit controller class to act as a controller for the edit page
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit {
    /** @var \mod_activequiz\activequiz Realtime quiz class. */
    protected $activequiz;

    /** @var string $action The specified action to take. */
    protected $action;

    /** @var object $context The specific context for this activity. */
    protected $context;

    /** @var \question_edit_contexts $contexts and array of contexts that has all parent contexts from the RTQ context. */
    protected $contexts;

    /** @var \moodle_url $pageurl The page url to base other calls on. */
    protected $pageurl;

    /** @var array $this ->pagevars An array of page options for the page load. */
    protected $pagevars;

    /** @var  \mod_activequiz\output\edit_renderer $renderer. */
    protected $renderer;

    /**
     * Sets up the edit page
     *
     * @param string $baseurl the base url of the
     *
     * @return array Array of variables that the page is set up with
     */
    public function setup_page($baseurl) {
        global $PAGE, $CFG, $DB;

        $this->pagevars = array();

        $pageurl = new \moodle_url($baseurl);
        $pageurl->remove_all_params();

        $id = optional_param('cmid', false, PARAM_INT);
        $quizid = optional_param('quizid', false, PARAM_INT);

        // get necessary records from the DB.
        if ($id) {
            $cm = get_coursemodule_from_id('activequiz', $id, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $quiz = $DB->get_record('activequiz', array('id' => $cm->instance), '*', MUST_EXIST);
        } else {
            $quiz = $DB->get_record('activequiz', array('id' => $quizid), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('activequiz', $quiz->id, $course->id, false, MUST_EXIST);
        }
        $this->get_parameters(); // get the rest of the parameters and set them in the class.

        if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $cm->id);
        } else {
            $this->context = \context_module::instance($cm->id);
        }

        // set up question lib.

        list($this->pageurl, $this->contexts, $cmid, $cm, $quiz, $this->pagevars) =
            question_edit_setup('editq', '/mod/activequiz/edit.php', true);


        $PAGE->set_url($this->pageurl);
        $this->pagevars['pageurl'] = $this->pageurl;

        $PAGE->set_title(strip_tags($course->shortname . ': ' . get_string("modulename", "activequiz")
            . ': ' . format_string($quiz->name, true)));
        $PAGE->set_heading($course->fullname);


        // setup classes needed for the edit page
        $this->activequiz = new \mod_activequiz\activequiz($cm, $course, $quiz, $this->pageurl, $this->pagevars, 'edit');
        $this->renderer = $this->activequiz->get_renderer(); // set the renderer for this controller.  Done really for code completion.

    }

    /**
     * Handles the action specified
     *
     */
    public function handle_action() {
        global $PAGE, $DB;

        // check if a session is open.  If so display error.
        if($sessions = $DB->get_records('activequiz_sessions', array('activequizid' => $this->activequiz->getRTQ()->id, 'sessionopen'=> '1'))){
            $this->renderer->print_header();
            $this->renderer->opensession();
            $this->renderer->footer();
            return; // return early to stop continuation.
        }


        switch ($this->action) {
            case 'dragdrop': // this is a javascript callack case for the drag and drop of questions using ajax.
                $jsonlib = new \mod_activequiz\utils\jsonlib();

                $questionorder = optional_param('questionorder', '', PARAM_RAW);

                if ($questionorder === '') {
                    $jsonlib->send_error('invalid request');
                }

                $questionorder = explode(',', $questionorder);

                if ($this->activequiz->get_questionmanager()->set_full_order($questionorder) === true) {
                    $jsonlib->set('success', 'true');
                    $jsonlib->send_response();
                } else {
                    $jsonlib->send_error('unable to re-sort questions');
                }

                break;
            case 'moveup':

                $questionid = required_param('questionid', PARAM_INT);

                if ($this->activequiz->get_questionmanager()->move_question('up', $questionid)) {
                    $type = 'success';
                    $message = get_string('qmovesuccess', 'activequiz');
                } else {
                    $type = 'error';
                    $message = get_string('qmoveerror', 'activequiz');
                }

                $this->renderer->setMessage($type, $message);
                $this->renderer->print_header();
                $this->list_questions();
                $this->renderer->footer();

                break;
            case 'movedown':

                $questionid = required_param('questionid', PARAM_INT);

                if ($this->activequiz->get_questionmanager()->move_question('down', $questionid)) {
                    $type = 'success';
                    $message = get_string('qmovesuccess', 'activequiz');
                } else {
                    $type = 'error';
                    $message = get_string('qmoveerror', 'activequiz');
                }

                $this->renderer->setMessage($type, $message);
                $this->renderer->print_header();
                $this->list_questions();
                $this->renderer->footer();

                break;
            case 'addquestion':

                $questionid = required_param('questionid', PARAM_INT);
                $this->activequiz->get_questionmanager()->add_question($questionid);

                break;
            case 'editquestion':

                $questionid = required_param('rtqquestionid', PARAM_INT);
                $this->activequiz->get_questionmanager()->edit_question($questionid);

                break;
            case 'deletequestion':

                $questionid = required_param('questionid', PARAM_INT);
                if ($this->activequiz->get_questionmanager()->delete_question($questionid)) {
                    $type = 'success';
                    $message = get_string('qdeletesucess', 'activequiz');
                } else {
                    $type = 'error';
                    $message = get_string('qdeleteerror', 'activequiz');
                }

                $this->renderer->setMessage($type, $message);
                $this->renderer->print_header();
                $this->list_questions();
                $this->renderer->footer();

                break;
            case 'listquestions':
                // default is to list the questions.
                $this->renderer->print_header();
                $this->list_questions();
                $this->renderer->footer();
                break;
        }
    }

    /**
     * Returns the RTQ instance
     *
     * @return \mod_activequiz\activequiz
     */
    public function getRTQ() {
        return $this->activequiz;
    }

    /**
     * Echos the list of questions using the renderer for activequiz.
     *
     */
    protected function list_questions() {

        $questionbankview = $this->get_questionbank_view();
        $questions = $this->activequiz->get_questionmanager()->get_questions();
        $this->renderer->listquestions($questions, $questionbankview);

    }

    /**
     * Gets the question bank view based on the options passed in at the page setup.
     *
     * @return string
     */
    protected function get_questionbank_view() {

        $qperpage = optional_param('qperpage', 10, PARAM_INT);
        $qpage = optional_param('qpage', 0, PARAM_INT);


        ob_start(); // capture question bank display in buffer to have the renderer render output.

        $questionbank = new \mod_activequiz\activequiz_question_bank_view($this->contexts, $this->pageurl, $this->activequiz->getCourse(), $this->activequiz->getCM());
        $questionbank->display('editq', $qpage, $qperpage, $this->pagevars['cat'], true, true, true);

        return ob_get_clean();
    }


    /**
     * Private function to get parameters
     *
     */
    private function get_parameters() {

        $this->action = optional_param('action', 'listquestions', PARAM_ALPHA);

    }

}

