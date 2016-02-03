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
 * The mod_activequiz question manually graded event.
 *
 * @package    mod_activequiz
 * @author     John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright  2015 University of Wisconsin - Madison
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_activequiz\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_quiz question manually graded event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int activequizid: the id of the quiz.
 *      - int attemptid: the id of the attempt.
 *      - int sessionid: the session id of the attempt
 *      - int slot: the question number in the attempt.
 * }
 *
 * @package    mod_activequiz
 * @since      Moodle 2.8
 * @author     John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright  2015 University of Wisconsin - Madison
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_answered extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'question';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventquestionanswered', 'mod_activequiz');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->relateduserid' answered the question with id '$this->objectid' for the attempt " .
        "with id '{$this->other['attemptid']}' for the quiz with course module id '$this->contextinstanceid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/activequiz/quizdata.php', array('attempt'   => $this->other['attemptid'],
                                                                     'sessionid' => $this->other['sessionid'], 'action' => 'saveanswer', 'questionid' => $this->objectid));
    }


    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['attemptid'])) {
            throw new \coding_exception('The \'attemptid\' value must be set in other.');
        }

    }
}
