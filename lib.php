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
 * Library of functions and constants for module activequiz
 *
 * @package   mod_activequiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted activequiz record
 **/
function activequiz_add_instance($activequiz) {
    global $DB;

    $activequiz->timemodified = time();
    $activequiz->timecreated = time();
    if (empty($activequiz->graded)) {
        $activequiz->graded = 0;
        $activequiz->scale = 0;
    }

    // add all review options to the db object in the review options field.
    $activequiz->reviewoptions = activequiz_process_review_options($activequiz);

    $activequiz->id = $DB->insert_record('activequiz', $activequiz);

    activequiz_after_add_or_update($activequiz);

    return $activequiz->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function activequiz_update_instance($activequiz) {
    global $DB, $PAGE;

    $activequiz->timemodified = time();
    $activequiz->id = $activequiz->instance;
    if (empty($activequiz->graded)) {
        $activequiz->graded = 0;
        $activequiz->scale = 0;
    }
    // add all review options to the db object in the review options field.
    $activequiz->reviewoptions = activequiz_process_review_options($activequiz);

    $DB->update_record('activequiz', $activequiz);

    activequiz_after_add_or_update($activequiz);

    // after updating grade item we need to re-grade the sessions
    $activequiz = $DB->get_record('activequiz', array('id' => $activequiz->id));  // need the actual db record
    $course = $DB->get_record('course', array('id' => $activequiz->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('activequiz', $activequiz->id, $course->id, false, MUST_EXIST);
    $rtq = new \mod_activequiz\activequiz($cm, $course, $activequiz, array('pageurl' => $PAGE->url));
    $rtq->get_grader()->save_all_grades();


    return true;
}

/**
 * Proces the review options on the quiz settings page
 *
 * @param \mod_activequiz\activequiz $activequiz
 * @return string
 */
function activequiz_process_review_options($activequiz) {

    $afterreviewoptions = \mod_activequiz\activequiz::get_review_options_from_form($activequiz, 'after');

    $reviewoptions = new stdClass();
    $reviewoptions->after = $afterreviewoptions;

    // add all review options to the db object in the review options field.
    return json_encode($reviewoptions);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function activequiz_delete_instance($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/mod/activequiz/locallib.php');
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/question/editlib.php');

    try {
        // make sure the record exists
        $activequiz = $DB->get_record('activequiz', array('id' => $id), '*', MUST_EXIST);

        // go through each session and then delete them (also deletes all attempts for them)
        $sessions = $DB->get_records('activequiz_sessions', array('activequizid' => $activequiz->id));
        foreach ($sessions as $session) {
            \mod_activequiz\activequiz_session::delete($session->id);
        }

        // delete all questions for this quiz
        $DB->delete_records('activequiz_questions', array('activequizid' => $activequiz->id));

        // finally delete the activequiz object
        $DB->delete_records('activequiz', array('id' => $activequiz->id));
    } catch(Exception $e) {
        return false;
    }

    return true;
}

/**
 * Function to call other functions for after add or update of a quiz settings page
 *
 * @param int $activequiz
 */
function activequiz_after_add_or_update($activequiz) {

    activequiz_grade_item_update($activequiz);
}

/**
 * Update the grade item depending on settings passed in
 *
 *
 * @param stdClass   $activequiz
 * @param array|null $grades
 *
 * @return int Returns GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED
 */
function activequiz_grade_item_update($activequiz, $grades = null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir . '/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $activequiz)) { // May not be always present.
        $params = array('itemname' => $activequiz->name, 'idnumber' => $activequiz->cmidnumber);
    } else {
        $params = array('itemname' => $activequiz->name);
    }

    if ($activequiz->graded == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($activequiz->graded == 1) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $activequiz->scale;
        $params['grademin'] = 0;

    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/activequiz', $activequiz->course, 'mod', 'activequiz', $activequiz->id, 0, $grades, $params);
}


/**
 * Update grades depending on the userid and other settings
 *
 * @param      $activequiz
 * @param int  $userid
 * @param bool $nullifnone
 *
 * @return int Returns GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED
 */
function activequiz_update_grades($activequiz, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    if (!$activequiz->graded) {
        return activequiz_grade_item_update($activequiz);

    } else if ($grades = \mod_activequiz\utils\grade::get_user_grade($activequiz, $userid)) {
        return activequiz_grade_item_update($activequiz, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;

        return activequiz_grade_item_update($activequiz, $grade);

    } else {
        return activequiz_grade_item_update($activequiz);
    }

}


/**
 * Reset the grade book
 *
 * @param        $courseid
 * @param string $type
 */
function activequiz_reset_gradebook($courseid, $type = '') {


}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function activequiz_cron() {
    return true;
}


//////////////////////////////////////////////////////////////////////////////////////
/// Any other activequiz functions go here.  Each of them must have a name that
/// starts with activequiz_


function activequiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea != 'question') {
        return false;
    }

    require_course_login($course, true, $cm);

    $questionid = (int)array_shift($args);

    if (!$quiz = $DB->get_record('activequiz', array('id' => $cm->instance))) {
        return false;
    }

    if (!$question = $DB->get_record('activequiz_question', array('id' => $questionid, 'quizid' => $cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_activequiz/$filearea/$questionid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file);

    return false;
}

/**
 * Called via pluginfile.php -> question_pluginfile to serve files belonging to
 * a question in a question_attempt when that attempt is a quiz attempt.
 *
 * @package  mod_quiz
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string   $component the name of the component we are serving files for.
 * @param string   $filearea the name of the file area.
 * @param int      $qubaid the attempt usage id.
 * @param int      $slot the id of a question in this quiz attempt.
 * @param array    $args the remaining bits of the file path.
 * @param bool     $forcedownload whether the user must be forced to download the file.
 * @param array    $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function mod_activequiz_question_pluginfile($course, $context, $component,
                                            $filearea, $qubaid, $slot, $args, $forcedownload, array $options = array()) {
    global $CFG;
    //require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    /*
    $attemptobj = quiz_attempt::create_from_usage_id($qubaid);
    require_login($attemptobj->get_course(), false, $attemptobj->get_cm());

    if ($attemptobj->is_own_attempt() && !$attemptobj->is_finished()) {
        // In the middle of an attempt.
        if (!$attemptobj->is_preview_user()) {
            $attemptobj->require_capability('mod/quiz:attempt');
        }
        $isreviewing = false;

    } else {
        // Reviewing an attempt.
        $attemptobj->check_review_capability();
        $isreviewing = true;
    }

    if (!$attemptobj->check_file_access($slot, $isreviewing, $context->id,
        $component, $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }*/

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function activequiz_supports($feature) {

    if (!defined('FEATURE_PLAGIARISM')) {
        define('FEATURE_PLAGIARISM', 'plagiarism');
    }

    // this plugin does support groups, just that the plugin code
    // manages it instead of using the Moodle provided functionality

    switch ($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_RATE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_PLAGIARISM:
            return false;
        case FEATURE_USES_QUESTIONS:
            return true;

        default:
            return null;
    }
}

