<?php

namespace mod_activequiz\reports\overview;

use mod_activequiz\reports\ireport;
use mod_activequiz\tableviews\overallgradesview;

class report_overview extends \mod_activequiz\reports\activequiz_report_base implements ireport {

    /**
     * The tableview for the current request.  is added by the handle request function.
     *
     * @var
     */
    protected $tableview;

    /**
     * @var \mod_activequiz\output\report_overview_renderer $renderer
     */
    protected $renderer;

    /**
     * report_overview constructor.
     * @param \mod_activequiz\activequiz $activequiz
     */
    public function __construct(\mod_activequiz\activequiz $activequiz) {
        global $PAGE;

        $this->renderer = $PAGE->get_renderer('mod_activequiz', 'report_overview');
        parent::__construct($activequiz);
    }

    /**
     * Handle the request for this specific report
     *
     * @param \moodle_url $pageurl
     * @param array $pagevars
     * @return void
     */
    public function handle_request($pageurl, $pagevars) {

        $this->renderer->init($this->activequiz, $pageurl, $pagevars);

        // switch the action
        switch($pagevars['action']) {
            case 'regradeall':

                if($this->activequiz->get_grader()->save_all_grades(true)) {
                    $this->renderer->setMessage('success',  get_string('successregrade', 'activequiz'));
                }else {
                    $this->renderer->setMessage('error',  get_string('errorregrade', 'activequiz'));
                }

                $sessions = $this->activequiz->get_sessions();
                $this->renderer->showMessage();
                $this->renderer->select_session($sessions);
                $this->renderer->home();

                break;
            case 'viewsession':

                $session_id = required_param('sessionid', PARAM_INT);

                if (empty($session_id)) { // if no session id just go to the home page

                    $redirecturl = new \moodle_url('/mod/activequiz/reports.php', [
                        'id' => $this->activequiz->getCM()->id,
                        'quizid' => $this->activequiz->getRTQ()->id
                    ]);
                    redirect($redirecturl, null, 3);
                }

                $session = $this->activequiz->get_session($session_id);
                $pageurl->param('sessionid', $session_id);
                $sessionattempts = new \mod_activequiz\tableviews\sessionattempts('sessionattempts', $this->activequiz,
                    $session, $pageurl);

                $sessions = $this->activequiz->get_sessions();
                $this->renderer->select_session($sessions, $session_id);
                $this->renderer->view_session_attempts($sessionattempts);


                break;
            default:

                $sessions = $this->activequiz->get_sessions();
                $this->renderer->select_session($sessions);
                $this->renderer->home();

                break;
        }

    }



}