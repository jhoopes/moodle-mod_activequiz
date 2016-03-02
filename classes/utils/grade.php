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

namespace mod_activequiz\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Grading utility class to handle grading functionality
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade {

    /** @var \mod_activequiz\activequiz */
    protected $rtq;

    /**
     * Gets the user grade, userid can be 0, which will return all grades for the activequiz
     *
     * @param $activequiz
     * @param $userid
     * @return array
     */
    public static function get_user_grade($activequiz, $userid = 0) {
        global $DB;

        $params = array($activequiz->id);
        $usertest = '';

        if (is_array($userid)) {
            // we have a group of userids
            if (count($userid) > 0) {
                list($usertest, $uparams) = $DB->get_in_or_equal($userid);
                $params = array_merge($params, $uparams);
                $usertest = 'AND u.id ' . $usertest;
            }
        } else if ($userid) {
            $params[] = $userid;
            $usertest = 'AND u.id = ?';
        }

        return $DB->get_records_sql("
            SELECT
                u.id,
                u.id AS userid,
                rtqg.grade AS rawgrade,
                rtqg.timemodified AS dategraded,
                MAX(rtqa.timefinish) AS datesubmitted

            FROM {user} u
            JOIN {activequiz_grades} rtqg ON u.id = rtqg.userid
            JOIN {activequiz_sessions} rtqs ON rtqg.activequizid = rtqs.activequizid
            JOIN {activequiz_attempts} rtqa ON rtqa.sessionid = rtqs.id

            WHERE rtqg.activequizid = ?
            $usertest
            GROUP BY u.id, rtqg.grade, rtqg.timemodified", $params);

    }


    /**
     * Construct for the grade utility class
     *
     * @param \mod_activequiz\activequiz $activequiz
     */
    public function __construct($activequiz) {
        $this->rtq = $activequiz;
    }

    /**
     * Save and (re)calculate grades for this RTQ
     *
     * @param bool $regrade_attempts Regrade the question attempts themselves through the question engine
     * @return bool
     */
    public function save_all_grades($regrade_attempts = false) {

        $sessions = $this->rtq->get_sessions();

        if (empty($sessions)) {
            return true; // return true if the sessions are empty.
        }

        // If we're regrading attempts, send them off to be re-graded before processing all sessions.
        if($regrade_attempts) {
            $this->process_attempt_regrade($sessions);
        }

        // check which grading method tos end the right sessions to process.
        if ($this->rtq->getRTQ()->grademethod == \mod_activequiz\utils\scaletypes::activequiz_FIRSTSESSION) {
            // only grading first session.
            return $this->process_sessions(array($sessions[0]));
        } else if ($this->rtq->getRTQ()->grademethod == \mod_activequiz\utils\scaletypes::REALTIMQUIZ_LASTSESSION) {
            // only grading last session.

            return $this->process_sessions(array(end($sessions)));
        } else {
            // otherwise do all sessions.
            // other grading methods are processed later.

            return $this->process_sessions($sessions);
        }
    }

    /**
     * Process grades for a specific user
     *
     * @param int $userid
     *
     * @return bool
     */
    public function save_user_grades($userid) {

        $sessions = $this->rtq->get_sessions();

        if (empty($sessions)) {
            return true; // return true if the sessions are empty.
        }

        // check grading methods
        if ($this->rtq->getRTQ()->grademethod == \mod_activequiz\utils\scaletypes::activequiz_FIRSTSESSION) {
            // only grading first session.
            return $this->process_sessions(array($sessions[0]), $userid);
        } else if ($this->rtq->getRTQ()->grademethod == \mod_activequiz\utils\scaletypes::REALTIMQUIZ_LASTSESSION) {
            // only grading last session.

            return $this->process_sessions(array(end($sessions)), $userid);
        } else {
            // otherwise do all sessions.
            // other grading methods are processed later.

            return $this->process_sessions($sessions, $userid);
        }

    }

    /**
     * Regrade all question usage attempts for the given sessions.
     *
     * @param $sessions
     */
    protected function process_attempt_regrade($sessions) {

        foreach($sessions as $session) {
            /** @var \mod_activequiz\activequiz_session $session */

            if($session->get_session()->sessionopen === 1) {
                continue;  // don't regrade attempts for an open session.
            }

            $session_attempts = $session->getall_attempts(true);

            foreach($session_attempts as $attempt) {
                /** @var \mod_activequiz\activequiz_attempt $attempt */

                // regrade all questions for the question usage for this attempt.
                $attempt->get_quba()->regrade_all_questions();
                $attempt->save();
            }

        }
    }

    /**
     * Separated function to process grading for the provided sessions
     *
     * @param array $sessions
     * @param int   $userid If specified will only process grading with that particular user
     *
     * @return bool true on success
     */
    protected function process_sessions($sessions, $userid = null) {
        global $DB;

        if ($userid && $userid < 0) {
            return true; // Ignore attempts to update the grades for an anonymous user.
        }

        $sessionsgrades = array();
        foreach ($sessions as $session) {
            /** @var \mod_activequiz\activequiz_session $session */

            if ($session->get_session()->sessionopen === 1) {
                continue; // don't calculate grades for open sessions.
            }

            // process grades when userid is present.
            if (!is_null($userid)) {
                if (!isset($sessionsgrades[ $userid ])) {
                    $sessionsgrades[ $userid ] = array();
                }

                list($forgroupid, $grade) = $this->get_session_grade($session, $userid);

                if ($this->rtq->group_mode()) {
                    $this->calculate_group_grades($sessionsgrades, $forgroupid, $grade, $userid, $session->get_session()->id);
                } else {
                    $sessionsgrades[ $userid ][ $session->get_session()->id ] = $grade;
                }
            } else { // extra processing to get user grades into it separately.

                // get and loop through the users for this session to get their grade.

                foreach ($session->get_session_users() as $user) {
                    $uid = $user->userid;
                    if ($uid < 0) {
                        continue; // Ignore anonymous users.
                    }
                    if (!isset($sessionsgrades[ $uid ])) { // add the userid to the sessions grade as an array.
                        $sessionsgrades[ $uid ] = array();
                    }
                    list($forgroupid, $grade) = $this->get_session_grade($session, $uid);

                    if ($this->rtq->group_mode()) {
                        $this->calculate_group_grades($sessionsgrades, $forgroupid, $grade, $uid, $session->get_session()->id);
                    } else {
                        $sessionsgrades[ $uid ][ $session->get_session()->id ] = $grade;
                    }
                }
            }
        }

        $grades = $this->calculate_grade($sessionsgrades);

        // run the whole thing on a transaction (persisting to our table and gradebook updates).
        $transaction = $DB->start_delegated_transaction();

        // now that we have the final grades persist the grades to activequiz grades table.
        $this->persist_grades($grades, $transaction);

        // update grades to gradebookapi.
        $updated = activequiz_update_grades($this->rtq->getRTQ(), array_keys($grades));


        if ($updated === GRADE_UPDATE_FAILED) {
            $transaction->rollback(new \Exception('Unable to save grades to gradebook'));
        }

        // Allow commit if we get here
        $transaction->allow_commit();

        // if everything passes to here return true
        return true;
    }


    /**
     * Get the session's grade
     *
     * For now this will always be the last attempt for the user
     *
     * @param \mod_activequiz\activequiz_session $session
     * @param int                                $userid The userid to get the grade for
     * @return array($forgroupid, $number)
     */
    protected function get_session_grade($session, $userid) {

        // get all attempts for the specified userid that are closed and are not previews
        // also skip checking for groups as grading handles groups separately
        $attempts = $session->getall_attempts(false, 'closed', $userid, true);
        $attemptno = count($attempts);
        if ($attemptno === 0) {
            return array(0, 0);
        }
        // get the last attempt for the user
        $attemptgraded = $this->get_last_attempt($attempts);

        return array($attemptgraded->forgroupid, $this->calculate_attempt_grade($attemptgraded));
    }


    /**
     * Calculate the overall grade for the RTQ based on the session scaling options
     *
     * @param array $sessionsgrades
     * @return array $grades, the actual points grade for each user present in sessionsgrades
     */
    protected function calculate_grade($sessionsgrades) {

        $grades = array();
        foreach ($sessionsgrades as $userid => $usgrades) {
            $grades[ $userid ] = $this->apply_session_grading_method($usgrades);
        }

        return $grades;
    }

    /**
     * Applies the grading method chosen
     *
     * @param array $grades The grades for each session for a particular user
     * @return number
     * @throws \Exception When there is no valid scaletype throws new exception
     */
    protected function apply_session_grading_method($grades) {

        switch ($this->rtq->getRTQ()->grademethod) {
            case \mod_activequiz\utils\scaletypes::activequiz_FIRSTSESSION:

                // take the first record (as there should only be one since it was filtered out earlier)
                reset($grades);

                return current($grades);

                break;
            case \mod_activequiz\utils\scaletypes::REALTIMQUIZ_LASTSESSION:

                // take the last grade (there should only be one, as the last session was filtered out earlier)
                return end($grades);

                break;
            case \mod_activequiz\utils\scaletypes::activequiz_SESSIONAVERAGE:

                // average the grades
                $gradecount = count($grades);

                $gradetotal = 0;
                foreach ($grades as $grade) {
                    $gradetotal = $gradetotal + $grade;
                }

                return $gradetotal / $gradecount;

                break;
            case \mod_activequiz\utils\scaletypes::activequiz_HIGHESTSESSIONGRADE:

                // find the highest grade
                $highestgrade = 0;
                foreach ($grades as $grade) {
                    if ($grade > $highestgrade) {
                        $highestgrade = $grade;
                    }
                }

                return $highestgrade;

                break;
            default:
                throw new \Exception('Invalid session grade method');
                break;
        }
    }

    /**
     * Calculate the grade for attempt passed in
     *
     * This function does the scaling down to what was desired in the activequiz settings
     * from what the quiz was actually set up with
     *
     * Is public function so that tableviews can get an attempt calculated grade
     *
     * @param \mod_activequiz\activequiz_attempt $attempt
     * @return number The grade to save
     */
    public function calculate_attempt_grade($attempt) {

        $quba = $attempt->get_quba();

        $totalpoints = 0;
        $totalslotpoints = 0;
        foreach ($attempt->getSlots() as $slot) {
            $totalpoints = $totalpoints + $quba->get_question_max_mark($slot);

            $slotpoints = $quba->get_question_mark($slot);
            if (!empty($slotpoints)) {
                $totalslotpoints = $totalslotpoints + $slotpoints;
            }
        }

        // use cross multiplication to scale to the desired points
        $scaledpoints = ($totalslotpoints * $this->rtq->getRTQ()->scale) / $totalpoints;

        return $scaledpoints;

    }

    /**
     * Persist the passed in grades (keyed by userid) to the database
     *
     * @param array               $grades
     * @param \moodle_transaction $transaction
     *
     * @return bool
     */
    protected function persist_grades($grades, \moodle_transaction $transaction) {
        global $DB;

        foreach ($grades as $userid => $grade) {

            if ($usergrade = $DB->get_record('activequiz_grades', array('userid' => $userid, 'activequizid' => $this->rtq->getRTQ()->id))) {
                // we're updating

                $usergrade->grade = $grade;
                $usergrade->timemodified = time();

                if (!$DB->update_record('activequiz_grades', $usergrade)) {
                    $transaction->rollback(new \Exception('Can\'t update user grades'));
                }
            } else {
                // we're adding

                $usergrade = new \stdClass();
                $usergrade->activequizid = $this->rtq->getRTQ()->id;
                $usergrade->userid = $userid;
                $usergrade->grade = $grade;
                $usergrade->timemodified = time();

                if (!$DB->insert_record('activequiz_grades', $usergrade)) {
                    $transaction->rollback(new \Exception('Can\'t insert user grades'));
                }

            }
        }

        return true;
    }


    /**
     * Get the last attempt of an attempts array.  Will be sorted by time finish first
     *
     * @param array $attempts
     *
     * @return \mod_activequiz\activequiz_attempt
     */
    protected function get_last_attempt($attempts) {

        // sort attempts by time finish
        usort($attempts, array('\mod_activequiz\activequiz_attempt', 'sortby_timefinish'));

        return end($attempts);

    }

    /**
     * Figures out the grades for group members if the quiz was taken in group mode
     *
     * IMPORTANT: IF A USER IS IN MORE THAN 1 GROUP FOR THE SPECIFIED GROUPING, THIS FUNCTION
     * WILL TAKE THE HIGHER OF THE 2 GRADES THAT WILL BE GIVEN TO THEM
     *
     * @param array  $sessionsgrades Array of session grades being built for user
     * @param int    $forgroupid
     * @param number $grade
     * @param int    $uid
     * @param int    $sessionid
     */
    protected function calculate_group_grades(&$sessionsgrades, $forgroupid, $grade, $uid, $sessionid) {

        if (empty($forgroupid)) {
            // just add the grade for the userid to the sessiongrades
            $sessionsgrades[ $uid ][ $sessionid ] = $grade;

            return;
        }

        // get the group attendance if we have it
        if ($this->rtq->getRTQ()->groupattendance == 1) {
            $groupattendance = $this->rtq->get_groupmanager()->get_attendance(null, null, null, $forgroupid);

            foreach ($groupattendance as $gattendanceuser) {
                $this->add_group_grade($sessionsgrades, $gattendanceuser->userid, $sessionid, $grade);
            }
        } else {
            $groupusers = $this->rtq->get_groupmanager()->get_group_members($forgroupid);
            foreach ($groupusers as $guser) {
                $this->add_group_grade($sessionsgrades, $guser->id, $sessionid, $grade);
            }
        }
    }

    /**
     * Figure out the grade to add to the particular group user.
     *
     * If they already have a grade for the session, check to see if the new grade is higher and,
     * if so, make that grade their grade instead of the lower grade.  This is a "fix" for the possibility
     * that a user is in more than 1 group that attempted the quiz
     *
     * @param array  $sessionsgrades
     * @param int    $guserid
     * @param int    $sessionid
     * @param number $grade
     */
    protected function add_group_grade(&$sessionsgrades, $guserid, $sessionid, $grade) {
        // check to see if a session grade already exists for the userid
        // this could happen if the user is a part of more than 1 group
        if (isset($sessionsgrades[ $guserid ][ $sessionid ])) {
            // if it does, then only replace the grade with the new grade if its higher
            if ($sessionsgrades[ $guserid ][ $sessionid ] < $grade) {
                $sessionsgrades[ $guserid ][ $sessionid ] = $grade;
            }
        } else { // if no grade is present for this user and session add the grade for the user
            if (!isset($sessionsgrades[ $guserid ])) { // this is for a group member who hasn't gotten a grade
                $sessionsgrades[ $guserid ] = array();
            }
            $sessionsgrades[ $guserid ][ $sessionid ] = $grade;
        }
    }

}

