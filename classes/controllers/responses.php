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

/**
 * The responses controller
 *
 * @package     mod_activequiz
 * @author      John Hoopes <hoopes@wisc.edu>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class responses
{

    /** @var \mod_activequiz\activequiz Realtime quiz class */
    protected $RTQ;

    /** @var \mod_activequiz\activequiz_session $session The session class for the activequiz view */
    protected $session;

    /** @var \moodle_url $pageurl The page url to base other calls on */
    protected $pageurl;

    /** @var array $this->pagevars An array of page options for the page load */
    protected $pagevars;

    /**
     * set up the class for the view page
     *
     * @param string $baseurl the base url of the page
     */
    public function setup_page($baseurl){
        global $PAGE, $CFG, $DB;

        $this->pagevars = array();

        $this->pageurl = new \moodle_url($baseurl);
        $this->pageurl->remove_all_params();

        $id = optional_param('id', false, PARAM_INT);
        $quizid = optional_param('quizid', false, PARAM_INT);

        // get necessary records from the DB
        if($id) {
            $cm = get_coursemodule_from_id('activequiz', $id, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $quiz = $DB->get_record('activequiz', array('id' => $cm->instance), '*', MUST_EXIST);
        } else {
            $quiz = $DB->get_record('activequiz', array('id' => $quizid), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('activequiz', $quiz->id, $course->id, false, MUST_EXIST);
        }
        $this->get_parameters(); // get the rest of the parameters and set them in the class

        require_login($course->id, false, $cm);


        $this->pageurl->param('id', $cm->id);
        $this->pageurl->param('quizid', $quiz->id);
        $this->pageurl->param('action', $this->pagevars['action']);
        $this->pagevars['pageurl'] = $this->pageurl;

        $this->RTQ = new \mod_activequiz\activequiz($cm, $course, $quiz, $this->pagevars);
        $this->RTQ->require_capability('mod/activequiz:seeresponses');

        // set up renderer
        $this->RTQ->get_renderer()->init($this->RTQ, $this->pageurl, $this->pagevars);


        $PAGE->set_pagelayout('incourse');
        $PAGE->set_context($this->RTQ->getContext());
        $PAGE->set_title(strip_tags($course->shortname . ': ' . get_string("modulename", "activequiz") . ': ' .
                                                                                    format_string($quiz->name, true)));
        $PAGE->set_heading($course->fullname);
        $PAGE->set_url($this->pageurl);
    }


    /**
     * Handles the page request
     *
     */
    public function handle_request(){
        global $DB;

        switch($this->pagevars['action']){
            case 'regradeall':

                $this->RTQ->get_grader()->save_all_grades();
                $this->RTQ->get_renderer()->setMessage('success', 'Successfully re-graded quiz');
                $sessions = $this->RTQ->get_sessions();
                $this->RTQ->get_renderer()->responses_header();
                $this->RTQ->get_renderer()->select_session($sessions);
                $this->RTQ->get_renderer()->report_home();
                $this->RTQ->get_renderer()->responses_footer();

                break;
            case 'viewsession':
                $sessionid = required_param('sessionid', PARAM_INT);

                if(empty($sessionid)){ // if no session id just go to the home page
                    $this->pageurl->param('action', '');
                    redirect($this->pageurl, null, 0);
                }

                $session = $this->RTQ->get_session($sessionid);
                $this->pageurl->param('sessionid');
                $sessionattempts = new \mod_activequiz\tableviews\sessionattempts('sessionattempts', $this->RTQ,
                                                                                            $session, $this->pageurl);

                $sessions = $this->RTQ->get_sessions();
                $this->RTQ->get_renderer()->responses_header();
                $this->RTQ->get_renderer()->select_session($sessions, $sessionid);
                $this->RTQ->get_renderer()->view_session_attempts($sessionattempts);
                $this->RTQ->get_renderer()->responses_footer();

                break;
            default:

                // default view is to show a report with the list of sessions
                // to select for showing the session's attempts
                $sessions = $this->RTQ->get_sessions();
                $this->RTQ->get_renderer()->responses_header();
                $this->RTQ->get_renderer()->select_session($sessions);
                $this->RTQ->get_renderer()->report_home();
                $this->RTQ->get_renderer()->responses_footer();
        }
    }

    /**
     * Gets the extra parameters for the class
     *
     */
    protected function get_parameters(){

        $this->pagevars['action'] = optional_param('action', '', PARAM_ALPHANUM);

    }
}

