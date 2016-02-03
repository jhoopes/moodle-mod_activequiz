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

namespace mod_activequiz;

defined('MOODLE_INTERNAL') || die();

use \mod_activequiz\forms\edit\add_question_form;

/**
 * Question manager class
 *
 * Provides utility functions to manage questions for a realtime quiz
 *
 * Basically this class provides an interface to internally map the questions added to a realtime quiz to
 * questions in the question bank.  calling get_questions() will return an ordered array of question objects
 * from the questions table and not the activequiz_questions table.  That table is only used internally by this
 * class.
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionmanager {

    /** @var activequiz */
    protected $rtq;

    /** @var array */
    protected $pagevars;

    /** @var \mod_activequiz_renderer */
    protected $renderer;

    /** @var array internal use only as we'll always just give out the qbank ordered questions */
    protected $rtqQuestions;

    /** @var array */
    protected $qbankOrderedQuestions;

    /** @var \moodle_url */
    protected $baseurl;


    /**
     * Construct an instance of question manager
     *
     * @param activequiz $rtq
     * @param \mod_activequiz_renderer $renderer The realtime quiz renderer to render visual elements
     * @param array $pagevars page variables array
     */
    public function __construct($rtq, $renderer, $pagevars = array())
    {
        global $DB;

        $this->rtq = $rtq;
        $this->renderer = $renderer;
        $this->pagevars = $pagevars;
        $this->orderedquestions = array();

        if ( !empty($this->pagevars) ) {
            $this->baseurl = $this->pagevars['pageurl'];
        } else {
            $params = array('id' => $this->rtq->getCM()->id);
            $this->baseurl = new \moodle_url('/mod/activequiz/edit.php', $params);
        }

        // load questions
        $this->refresh_questions();
    }

    /**
     * return this class's reference of activequiz
     *
     * @return activequiz
     */
    public function getRTQ()
    {
        return $this->rtq;
    }


    /**
     * Handles adding a question action from the question bank.
     *
     * Displays a form initially to ask how long they'd like the question to be set up for, and then after
     * valid input saves the question to the quiz at the last position
     *
     * @param int $questionid The question bank questionid
     *
     * @return mixed
     */
    public function add_question($questionid)
    {
        global $DB;

        // first check to see if the question has already been added
        if ( $this->is_question_already_present($questionid) ) {
            $redurl = clone($this->pagevars['pageurl']);
            /** @var \moodle_url $redurl */
            $redurl->remove_params('action'); // go back to base edit page

            redirect($redurl, get_string('cantaddquestiontwice', 'activequiz'));
        }

        $actionurl = clone($this->baseurl);
        $actionurl->param('action', 'addquestion');
        $actionurl->param('questionid', $questionid);

        $qrecord = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);

        $mform = new add_question_form($actionurl,
            array(
                'rtq' => $this->rtq,
                'questionname' => $qrecord->name,
                'defaultmark' => $qrecord->defaultmark,
                'showhistoryduringquiz' => 0,
                'edit' => false));

        // form handling
        if ( $mform->is_cancelled() ) {
            // redirect back to list questions page
            $this->baseurl->remove_params('action');
            redirect($this->baseurl, null, 0);

        } else if ( $data = $mform->get_data() ) {
            // process data from the form

            $question = new \stdClass();
            $question->activequizid = $this->rtq->getRTQ()->id;
            $question->questionid = $questionid;
            $question->notime = $data->notime;
            $question->questiontime = $data->indvquestiontime;
            $question->tries = $data->numberoftries;
            $question->points = number_format($data->points, 2);
            $question->showhistoryduringquiz = $data->showhistoryduringquiz;

            $RTQquestionid = $DB->insert_record('activequiz_questions', $question);

            $this->update_questionorder('addquestion', $RTQquestionid);

            // ensure there is no action or questionid in the baseurl
            $this->baseurl->remove_params('action', 'questionid');
            redirect($this->baseurl, null, 0);

        } else {
            // display the form
            $this->renderer->print_header();
            $this->renderer->addquestionform($mform);
            $this->renderer->footer();
        }
    }

    /**
     * Edit a RTQ question
     *
     * @param int $questionid the RTQ questionid
     *
     * @return mixed
     */
    public function edit_question($questionid)
    {
        global $DB;

        $actionurl = clone($this->baseurl);
        $actionurl->param('action', 'editquestion');
        $actionurl->param('rtqquestionid', $questionid);

        $rtqquestion = $DB->get_record('activequiz_questions', array('id' => $questionid), '*', MUST_EXIST);
        $qrecord = $DB->get_record('question', array('id' => $rtqquestion->questionid), '*', MUST_EXIST);

        $mform = new add_question_form($actionurl,
            array(
                'rtq' => $this->rtq,
                'questionname' => $qrecord->name,
                'defaultmark' => $qrecord->defaultmark,
                'showhistoryduringquiz' => $rtqquestion->showhistoryduringquiz,
                'edit' => true));

        // form handling
        if ( $mform->is_cancelled() ) {
            // redirect back to list questions page
            $this->baseurl->remove_params('action');
            redirect($this->baseurl, null, 0);

        } else if ( $data = $mform->get_data() ) {
            // process data from the form

            if ( number_format($data->points, 2) != $rtqquestion->points ) {
                // if we have a different points, update any existing sessions/attempts max points and regrade.

                $this->update_points(number_format($data->points, 2), $rtqquestion, $qrecord);

            }


            $question = new \stdClass();
            $question->id = $rtqquestion->id;
            $question->activequizid = $this->rtq->getRTQ()->id;
            $question->questionid = $rtqquestion->questionid;
            $question->notime = $data->notime;
            $question->questiontime = $data->indvquestiontime;
            $question->tries = $data->numberoftries;
            $question->points = number_format($data->points, 2);

            $DB->update_record('activequiz_questions', $question);

            // ensure there is no action or questionid in the baseurl
            $this->baseurl->remove_params('action', 'questionid');
            redirect($this->baseurl, null, 0);

        } else {
            // display the form
            $mform->set_data(array('indvquestiontime' => $rtqquestion->questiontime, 'notime' => $rtqquestion->notime, 'numberoftries' => $rtqquestion->tries, 'points' => $rtqquestion->points));
            $this->renderer->print_header();
            $this->renderer->addquestionform($mform);
            $this->renderer->footer();
        }
    }

    /**
     * Delete a question on the quiz
     *
     * @param int $questionid The RTQ questionid to delete
     *
     * @return bool
     */
    public function delete_question($questionid)
    {
        global $DB;

        try {
            $DB->delete_records('activequiz_questions', array('id' => $questionid));
            $this->update_questionorder('deletequestion', $questionid);
        } catch(\Exception $e) {
            return false; // return false on error
        }

        // if we get here return true
        return true;
    }

    /**
     * Moves a question on the question order for this quiz
     *
     * @param string $direction 'up'||'down'
     * @param int $questionid The RTQ questionid
     *
     * @return bool
     */
    public function move_question($direction, $questionid)
    {

        if ( $direction !== 'up' && $direction != 'down' ) {
            return false; // return false if the direction is not up or down
        }

        return $this->update_questionorder('movequestion' . $direction, $questionid);
    }

    /**
     * Public API function for setting the full order of the questions on the activequiz
     *
     * Please note that full order must be an array with no specialized keys as only array values are taken
     *
     * @param array $fullorder
     * @return bool
     */
    public function set_full_order($fullorder = array())
    {

        if ( !is_array($fullorder) ) {
            return false;
        }

        $fullorder = array_values($fullorder);

        return $this->update_questionorder('replaceorder', null, $fullorder);
    }

    /**
     * Returns the questions in the specified question order
     *
     * @return array of the question bank ordered questions of \mod_activequiz\activequiz_question objects
     */
    public function get_questions()
    {
        return $this->qbankOrderedQuestions;
    }

    /**
     * Gets the question type for the specified question number
     *
     * @param int $qnum The question number to get the questiontype
     *
     *
     * @return string
     */
    public function get_questiontype_byqnum($qnum)
    {

        // get the actual key for the qbank question
        $qbankkeys = array_keys($this->qbankOrderedQuestions);
        $desiredkey = $qbankkeys[$qnum - 1];
        $rtqQuestion = $this->qbankOrderedQuestions[$desiredkey];

        return $rtqQuestion->getQuestion()->qtype;
    }

    /**
     * shortcut to get the first question
     *
     * @param \mod_activequiz\activequiz_attempt $attempt
     *
     * @return \mod_activequiz\activequiz_question
     */
    public function get_first_question($attempt)
    {
        return $this->get_question_with_slot(1, $attempt);
    }

    /**
     * Gets a activequiz_question object with the slot set
     *
     * @param int $slotnum The index of the slot we want, i.e. the question number
     * @param \mod_activequiz\activequiz_attempt $attempt The current attempt
     *
     * @return \mod_activequiz\activequiz_question
     */
    public function get_question_with_slot($slotnum, $attempt)
    {

        $slots = $attempt->getSlots();
        $quba = $attempt->get_quba();

        // first check if this is the last question
        if ( empty($slots[$slotnum]) ) {
            $attempt->islastquestion(true);
        } else {
            $attempt->islastquestion(false);
        }

        // since arrays are indexed starting at 0 and we reference questions starting with 1, we subtract 1
        $slotnum = $slotnum - 1;


        // get the first question
        $qubaQuestion = $quba->get_question($slots[$slotnum]);

        foreach ($this->qbankOrderedQuestions as $qbankQuestion) {
            /** @var \mod_activequiz\activequiz_question $qbankQuestion */

            if ( $qbankQuestion->getQuestion()->id == $qubaQuestion->id ) {
                // set the slot on the qbank question as this is the actual id we're using for question number
                $qbankQuestion->set_slot($slots[$slotnum]);

                return $qbankQuestion;
            }
        }

        // if we get here return null due to no question
        return null;
    }


    /**
     * add the questions to the question usage
     * This is called by the question_attmept class on construct of a new attempt
     *
     * @param \question_usage_by_activity $quba
     *
     * @return array
     */
    public function add_questions_to_quba(\question_usage_by_activity $quba)
    {

        // we need the questionids of our questions
        $questionids = array();
        foreach ($this->qbankOrderedQuestions as $qbankquestion) {
            /** @var activequiz_question $qbankquestion */

            if ( !in_array($qbankquestion->getQuestion()->id, $questionids) ) {
                $questionids[] = $qbankquestion->getQuestion()->id;
            }
        }
        $questions = question_load_questions($questionids);

        // loop through the ordered question bank questions and add them to the quba
        // object
        $attemptlayout = array();
        foreach ($this->qbankOrderedQuestions as $qbankquestion) {

            $questionid = $qbankquestion->getQuestion()->id;
            $q = \question_bank::make_question($questions[$questionid]);
            $attemptlayout[$qbankquestion->getId()] = $quba->add_question($q, $qbankquestion->getPoints());
        }

        // start the questions in the quba
        $quba->start_all_questions();

        /**
         * return the attempt layout which is a set of ids that are the slot ids from the question engine usage by activity instance
         * these are what are used during an actual attempt rather than the questionid themselves, since the question engine will handle
         * the translation
         */

        return $attemptlayout;
    }

    /**
     * Gets the question order from the rtq object
     *
     * @return string
     */
    protected function get_question_order()
    {
        return $this->rtq->getRTQ()->questionorder;
    }

    /**
     * Updates question order on RTQ object and then persists to the database
     *
     * @param string
     * @return bool
     */
    protected function set_question_order($questionorder)
    {

        $this->rtq->getRTQ()->questionorder = $questionorder;

        return $this->rtq->saveRTQ();

    }

    /**
     * Updates the question order for the question manager
     *
     * @param string $action
     * @param int $questionid the realtime quiz question id, NOT the question engine question id
     * @param array $fullorder An array of question objects to sort as is.
     *                         This is mainly used for the dragdrop callback on the edit page.  If the full order is not specified
     *                         with all questions currently on the quiz, the case will return false
     *
     * @return bool true/false if it was successful
     */
    protected function update_questionorder($action, $questionid, $fullorder = array())
    {

        switch ($action) {
            case 'addquestion':

                $questionorder = $this->get_question_order();
                if ( empty($questionorder) ) {
                    $questionorder = $questionid;
                } else {
                    $questionorder .= ',' . $questionid;
                }

                $this->set_question_order($questionorder);

                // refresh question list
                $this->refresh_questions();

                return true;
                break;
            case 'deletequestion':

                $questionorder = $this->get_question_order();
                $questionorder = explode(',', $questionorder);

                foreach ($questionorder as $index => $qorder) {

                    if ( $qorder == $questionid ) {
                        unset($questionorder[$index]);
                        break;
                    }
                }
                $newquestionorder = implode(',', $questionorder);

                // set the question order and refresh the questions
                $this->set_question_order($newquestionorder);
                $this->refresh_questions();

                return true;

                break;
            case 'movequestionup':

                $questionorder = $this->get_question_order();
                $questionorder = explode(',', $questionorder);

                foreach ($questionorder as $index => $qorder) {

                    if ( $qorder == $questionid ) {

                        if ( $index == 0 ) {
                            return false; // can't move first question up
                        }

                        // if ids match replace the previous index with the current one
                        // and make the previous index qid the current index
                        $prevqorder = $questionorder[$index - 1];
                        $questionorder[$index - 1] = $questionid;
                        $questionorder[$index] = $prevqorder;
                        break;
                    }
                }

                $newquestionorder = implode(',', $questionorder);

                // set the question order and refresh the questions
                $this->set_question_order($newquestionorder);
                $this->refresh_questions();

                return true;

                break;
            case 'movequestiondown':

                $questionorder = $this->get_question_order();
                $questionorder = explode(',', $questionorder);

                $questionordercount = count($questionorder);

                foreach ($questionorder as $index => $qorder) {

                    if ( $qorder == $questionid ) {

                        if ( $index == $questionordercount - 1 ) {
                            return false; // can't move last question down
                        }

                        // if ids match replace the next index with the current one
                        // and make the next index qid the current index
                        $nextqorder = $questionorder[$index + 1];
                        $questionorder[$index + 1] = $questionid;
                        $questionorder[$index] = $nextqorder;
                        break;
                    }
                }

                $newquestionorder = implode(',', $questionorder);

                // set the question order and refresh the questions
                $this->set_question_order($newquestionorder);
                $this->refresh_questions();

                return true;

                break;
            case 'replaceorder':

                $questionorder = $this->get_question_order();
                $questionorder = explode(',', $questionorder);

                // if we don't have the same number of questions return error
                if ( count($fullorder) !== count($questionorder) ) {
                    return false;
                }

                // next validate that the questions sent all match to a question in the current order
                $allmatch = true;
                foreach ($questionorder as $qorder) {
                    if ( !in_array($qorder, $fullorder) ) {
                        $allmatch = false;
                    }
                }

                if ( $allmatch ) {

                    $newquestionorder = implode(',', $fullorder);
                    $this->set_question_order($newquestionorder);
                    $this->refresh_questions();

                    return true;
                } else {
                    return false;
                }

                break;
        }

        return false; // if we get here, there's an error so return false
    }

    /**
     * check whether the question id has already been added
     *
     * @param int $questionid
     *
     * @return bool
     */
    protected function is_question_already_present($questionid)
    {

        // loop through the db rtq questions and see if we find a match
        foreach ($this->rtqQuestions as $dbRTQquestion) {
            if ( $dbRTQquestion->questionid == $questionid ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Refreshes question information from the DB
     *
     * This is the function that should be called so that questions are loaded
     * in the correct order
     *
     */
    protected function refresh_questions()
    {
        $this->init_rtq_questions();
        $this->init_qbank_questions();
    }

    /**
     * Gets the list of questions from the DB
     *
     */
    private function init_rtq_questions()
    {
        global $DB;
        $this->rtqQuestions = $DB->get_records('activequiz_questions', array('activequizid' => $this->rtq->getRTQ()->id));
    }

    /**
     * Orders the real time questions and then
     * puts question bank ordered questions into the qbankorderedquestions var
     *
     */
    private function init_qbank_questions()
    {
        global $DB;

        // start by ordering the RTQ question ids into an array
        $questionorder = $this->rtq->getRTQ()->questionorder;

        // generate empty array for ordered questions for no question order
        if ( empty($questionorder) ) {

            $this->qbankOrderedQuestions = array();

            return;

        } else { // otherwise explode it and continue on
            $questionorder = explode(',', $questionorder);
        }

        // using the question order saved in rtq object, get the qbank question ids from the rtq questions
        $orderedquestionids = array();
        foreach ($questionorder as $qorder) {
            // store the rtq question id as the key so that it can be used later when adding question time to
            // question bank question object
            $orderedquestionids[$qorder] = $this->rtqQuestions[$qorder]->questionid;
        }

        // get qbank questions based on the question ids from the RTQ questions table
        list($sql, $params) = $DB->get_in_or_equal($orderedquestionids);
        $query = 'SELECT * FROM {question} WHERE id ' . $sql;
        $questions = $DB->get_records_sql($query, $params);

        // Now order the qbank questions based on the order that we got above
        $qbankOrderedQuestions = array();
        foreach ($orderedquestionids as $rtqqid => $questionid) { // use the ordered question ids we got earlier
            if ( !empty($questions[$questionid]) ) {

                // create realtime quiz question and add it to the array
                $quizquestion = new \mod_activequiz\activequiz_question($rtqqid,
                    $this->rtqQuestions[$rtqqid]->notime,
                    $this->rtqQuestions[$rtqqid]->questiontime,
                    $this->rtqQuestions[$rtqqid]->tries,
                    $this->rtqQuestions[$rtqqid]->points,
                    $this->rtqQuestions[$rtqqid]->showhistoryduringquiz,
                    $questions[$questionid]);

                $qbankOrderedQuestions[$rtqqid] = $quizquestion; // add question to the ordered questions
            }
        }

        $this->qbankOrderedQuestions = $qbankOrderedQuestions;
    }

    /**
     * @param float $newpoints
     * @param \stdClass $questionrecord
     * @param \stdClass $qrecord
     *
     * @throws \moodle_exception  Throws moodle exception when a slot isn't found, or if unable to grade
     * @return bool;
     */
    public function update_points($newpoints, $questionrecord, $qrecord)
    {
        global $DB;

        $q = new \mod_activequiz\activequiz_question(
            $questionrecord->id,
            $questionrecord->notime,
            $questionrecord->questiontime,
            $questionrecord->tries,
            $newpoints,
            $questionrecord->showhistoryduringquiz,
            $qrecord
        );

        $sessions = $this->rtq->get_sessions();

        foreach ($sessions as $session) {

            /** @var \mod_activequiz\activequiz_session $session */

            if ( $session->get_session()->sessionopen === 1 ) {
                continue;  // don't regrade attempts for an open session.
            }

            $session_attempts = $session->getall_attempts(true);

            foreach ($session_attempts as $attempt) {
                /** @var \mod_activequiz\activequiz_attempt $attempt */
                if ( $slot = $attempt->get_question_slot($q) ) {
                    $quba = $attempt->get_quba();
                    $quba->set_max_mark($slot, $newpoints);
                    $quba->regrade_question($slot, false, $newpoints);

                    $attempt->save();
                } else {
                    throw new \moodle_exception('invalidslot', 'mod_activequiz', '', null, $attempt->get_attempt());
                }
            }

        }
        // re-save all grades after regrading the question attempts for the slot.
        if ( $this->rtq->get_grader()->save_all_grades() ) {
            return true;
        }else {
            throw new \moodle_exception('cannotgrade', 'mod_activequiz');
        }
    }
}


