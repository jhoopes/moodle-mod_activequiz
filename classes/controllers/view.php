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

/**
 * view controller class for the view page
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view {

    /** @var \mod_activequiz\activequiz Realtime quiz class */
    protected $RTQ;

    /** @var \mod_activequiz\activequiz_session $session The session class for the activequiz view */
    protected $session;

    /** @var string $action The specified action to take */
    protected $action;

    /** @var object $context The specific context for this activity */
    protected $context;

    /** @var \question_edit_contexts $contexts and array of contexts that has all parent contexts from the RTQ context */
    protected $contexts;

    /** @var \moodle_url $pageurl The page url to base other calls on */
    protected $pageurl;

    /** @var array $this ->pagevars An array of page options for the page load */
    protected $pagevars;

    /**
     * set up the class for the view page
     *
     * @param string $baseurl the base url of the page
     */
    public function setup_page($baseurl) {
        global $PAGE, $CFG, $DB;

        $this->pagevars = array();

        $this->pageurl = new \moodle_url($baseurl);
        $this->pageurl->remove_all_params();

        $id = optional_param('id', false, PARAM_INT);
        $quizid = optional_param('quizid', false, PARAM_INT);

        // get necessary records from the DB
        if ($id) {
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

        $this->RTQ = new \mod_activequiz\activequiz($cm, $course, $quiz, $this->pageurl, $this->pagevars);
        $this->RTQ->require_capability('mod/activequiz:attempt');
        $this->pagevars['isinstructor'] = $this->RTQ->is_instructor(); // set this up in the page vars so it can be passed to things like the renderer

        // finally set up the question manager and the possible activequiz session
        $this->session = new \mod_activequiz\activequiz_session($this->RTQ, $this->pageurl, $this->pagevars);

        $PAGE->set_pagelayout('incourse');
        $PAGE->set_context($this->RTQ->getContext());
        $PAGE->set_cm($this->RTQ->getCM());
        $PAGE->set_title(strip_tags($course->shortname . ': ' . get_string("modulename", "activequiz") . ': ' .
            format_string($quiz->name, true)));
        $PAGE->set_heading($course->fullname);
        $PAGE->set_url($this->pageurl);


    }

    /**
     * Handle's the page request
     *
     */
    public function handle_request() {
        global $DB, $USER, $PAGE;

        // first check if there are questions or not.  If there are no questions display that message instead,
        // regardless of action.
        if (count($this->RTQ->get_questionmanager()->get_questions()) === 0) {
            $this->pagevars['action'] = 'noquestions';
            $this->pageurl->param('action', ''); // remove the action
        }


        switch ($this->pagevars['action']) {


            case 'noquestions':

                $this->RTQ->get_renderer()->view_header();
                $this->RTQ->get_renderer()->no_questions($this->RTQ->is_instructor());
                $this->RTQ->get_renderer()->view_footer();
                break;
            case 'quizstart':
                // case for the quiz start landing page

                // set the quiz view page to the base layout for 1 column layout
                $PAGE->set_pagelayout('base');

                if ($this->session->get_session() === false) {
                    // redirect them to the default page with a quick message first

                    $redirurl = clone($this->pageurl);
                    $redirurl->remove_params('action');

                    redirect($redirurl, get_string('nosession', 'activequiz'), 5);
                } else {

                    // this is here to help prevent race conditions for multiple group members trying to take the
                    // quiz at the same time
                    $cantakequiz = false;
                    if ($this->RTQ->group_mode()) {

                        if(!$this->RTQ->is_instructor() && $this->pagevars['group'] == 0){
                            print_error('invalidgroupid', 'mod_activequiz');
                        }

                        // check if the user can take the quiz for the group
                        if ($this->session->can_take_quiz_for_group($this->pagevars['group'])) {
                            $cantakequiz = true;
                        }
                    } else { // if no group mode, user will always be able to take quiz
                        $cantakequiz = true;
                    }

                    if ($cantakequiz) {
                        if (!$this->session->init_attempts($this->RTQ->is_instructor(), $this->pagevars['group'],
                            $this->pagevars['groupmembers'])
                        ) {
                            print_error('cantinitattempts', 'activequiz');
                        }

                        // set the session as running
                        if ($this->RTQ->is_instructor() && $this->session->get_session()->status == 'notrunning') {
                            $this->session->set_status('running');
                        }

                        // get the current attempt an initialize the head contributions
                        $attempt = $this->session->get_open_attempt();
                        $attempt->get_html_head_contributions();

                        $attempt->setStatus('inprogress');
                        // now show the quiz start landing page
                        $this->RTQ->get_renderer()->view_header(true);
                        $this->RTQ->get_renderer()->render_quiz($attempt, $this->session);
                        $this->RTQ->get_renderer()->view_footer();
                    } else {
                        $this->RTQ->get_renderer()->view_header();
                        $this->RTQ->get_renderer()->group_session_started();
                        $this->RTQ->get_renderer()->view_footer();
                    }

                }
                break;
            case 'selectgroupmembers':

                if (empty($this->pagevars['group'])) {
                    $viewhome = clone($this->pageurl);
                    $viewhome->remove_params('action');
                    redirect($viewhome, get_string('invalid_group_selected', 'activequiz'), 5);
                } else {
                    $this->pageurl->param('group', $this->pagevars['group']);
                    $groupselectform = new \mod_activequiz\forms\view\groupselectmembers(
                        $this->pageurl,
                        array(
                            'rtq'           => $this->RTQ,
                            'selectedgroup' => $this->pagevars['group']
                        ));

                    if ($data = $groupselectform->get_data()) {

                        // basically we want to get all gm* fields
                        $gmemnum = 1;
                        $groupmembers = array();
                        $data = get_object_vars($data);
                        while (isset($data[ 'gm' . $gmemnum ])) {
                            if ($data[ 'gm' . $gmemnum ] != 0) {
                                $groupmembers[] = $data[ 'gm' . $gmemnum ];
                            }
                            $gmemnum++;
                        }

                        $this->pageurl->param('groupmembers', implode(',', $groupmembers));
                        $this->pageurl->param('action', 'quizstart');
                        // redirect to the quiz start page
                        redirect($this->pageurl, null, 0);

                    } else {
                        $this->RTQ->get_renderer()->view_header();
                        $this->RTQ->get_renderer()->group_member_select($groupselectform);
                        $this->RTQ->get_renderer()->view_footer();
                    }
                }

                break;
            default:
                // default is to show view to start quiz (for instructors/quiz controllers) or join quiz (for everyone else)

                // trigger event for course module viewed
                $event = \mod_activequiz\event\course_module_viewed::create(array(
                    'objectid' => $PAGE->cm->instance,
                    'context'  => $PAGE->context,
                ));

                $event->add_record_snapshot('course', $this->RTQ->getCourse());
                $event->add_record_snapshot($PAGE->cm->modname, $this->RTQ->getRTQ());
                $event->trigger();

                // determine home display based on role
                if ($this->RTQ->is_instructor()) {
                    $startsessionform = new \mod_activequiz\forms\view\start_session($this->pageurl);

                    if ($data = $startsessionform->get_data()) {
                        // create a new quiz session

                        // first check to see if there are any open sessions
                        // this shouldn't occur, but never hurts to check
                        $sessions = $DB->get_records('activequiz_sessions', array(
                                'activequizid' => $this->RTQ->getRTQ()->id,
                                'sessionopen'  => 1
                            )
                        );

                        if (!empty($sessions)) {
                            // error out with that there are existing sessions
                            $this->RTQ->get_renderer()->setMessage(get_string('alreadyexisting_sessions', 'activequiz'), 'error');
                            $this->RTQ->get_renderer()->view_header();
                            $this->RTQ->get_renderer()->view_inst_home($startsessionform, $this->session->get_session());
                            $this->RTQ->get_renderer()->view_footer();
                            break;
                        } else {
                            if (!$this->session->create_session($data)) {
                                // error handling
                                $this->RTQ->get_renderer()->setMessage(get_string('unabletocreate_session', 'activequiz'), 'error');
                                $this->RTQ->get_renderer()->view_header();
                                $this->RTQ->get_renderer()->view_inst_home($startsessionform, $this->session->get_session());
                                $this->RTQ->get_renderer()->view_footer();
                                break; // break out of the switch
                            }
                        }
                        // redirect to the quiz start
                        $quizstarturl = clone($this->pageurl);
                        $quizstarturl->param('action', 'quizstart');
                        redirect($quizstarturl, null, 0);

                    } else {
                        $this->RTQ->get_renderer()->view_header();
                        $this->RTQ->get_renderer()->view_inst_home($startsessionform, $this->session->get_session());
                        $this->RTQ->get_renderer()->view_footer();
                    }
                } else {

                    // check to see if the group already started a quiz
                    $validgroups = array();
                    if ($this->RTQ->group_mode()) {
                        // if there is already an attempt for this session for this group for this user don't allow them to start another
                        $validgroups = $this->session->check_attempt_for_group();
                        if (empty($validgroups) && $validgroups !== false) {
                            $this->RTQ->get_renderer()->view_header();
                            $this->RTQ->get_renderer()->group_session_started();
                            $this->RTQ->get_renderer()->view_footer();
                            break;
                        } else if ($validgroups === false) {
                            $validgroups = array();
                        }
                    }
                    $studentstartformparams = array('rtq' => $this->RTQ, 'validgroups' => $validgroups);
                    $studentstartform = new \mod_activequiz\forms\view\student_start_form($this->pageurl, $studentstartformparams);
                    if ($data = $studentstartform->get_data()) {

                        $quizstarturl = clone($this->pageurl);
                        $quizstarturl->param('action', 'quizstart');

                        // if data redirect to the quiz start url with the group selected if we're in group mode
                        if ($this->RTQ->group_mode()) {
                            $groupid = $data->group;
                            $quizstarturl->param('group', $groupid);

                            // check if the group attendance feature is enabled
                            // if so redirect to the group member select form
                            // don't send to group attendance form if an attempt is already started
                            if ($this->RTQ->getRTQ()->groupattendance == 1 && !$this->session->get_open_attempt_for_current_user()) {
                                $quizstarturl->param('action', 'selectgroupmembers');
                            }

                            redirect($quizstarturl, null, 0);
                        } else {
                            redirect($quizstarturl, null, 0);
                        }

                    } else { // display student home.  (form will display only if there is an active session

                        $this->RTQ->get_renderer()->view_header();
                        $this->RTQ->get_renderer()->view_student_home($studentstartform, $this->session);
                        $this->RTQ->get_renderer()->view_footer();

                    }
                }


                break;
        }
    }


    /**
     * Gets the extra parameters for the class
     *
     */
    protected function get_parameters() {

        $this->pagevars['action'] = optional_param('action', '', PARAM_ALPHANUM);
        $this->pagevars['group'] = optional_param('group', '0', PARAM_INT);
        $this->pagevars['groupmembers'] = optional_param('groupmembers', '', PARAM_RAW);

    }

}

