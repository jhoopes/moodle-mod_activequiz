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
 * Simple callback page to handle the many hits for quiz status when running
 *
 * This is used so the javascript can act accordingly to the instructor's actions
 *
 * @package   mod_activequiz
 * @author    John Hoopes <hoopes@wisc.edu>
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_sesskey();
global $DB;

// if they've passed the sesskey information grab the session info
$sessionid = required_param('sessionid', PARAM_INT);

// get JSONlib to return json response
$jsonlib = new \mod_activequiz\utils\jsonlib();


if (!$session = $DB->get_record('activequiz_sessions', array('id' => $sessionid))) {
    $jsonlib->send_error('invalid session');
}

// if we have a session determine the response
if ($session->sessionopen == 0) {

    $jsonlib->set('status', 'sessionclosed');
    $jsonlib->send_response();

} else if (empty($session->currentquestion)) {
    // send a status of quiz not running
    $jsonlib->set('status', 'notrunning');
    $jsonlib->send_response();
} else if ($session->status == 'reviewing') {

    $jsonlib->set('status', 'reviewing');
    $jsonlib->send_response();

} else if ($session->status == 'endquestion') {

    $jsonlib->set('status', 'endquestion');
    $jsonlib->send_response();

} else {
    // otherwise send a response of the current question with the next start time
    $jsonlib->set('status', 'running');
    $jsonlib->set('currentquestion', $session->currentquestion);
    $jsonlib->set('questiontime', $session->currentquestiontime);
    $delay = $session->nextstarttime - time();
    $jsonlib->set('delay', $delay);
    $jsonlib->send_response();
}





