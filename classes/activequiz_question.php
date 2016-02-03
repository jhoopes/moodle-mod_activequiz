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

/**
 * A realtime quiz question object
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activequiz_question {

    /** @var int $id The RTQ question id */
    protected $id;

    /** @var int $notime Whether or not this question is timed */
    protected $notime;

    /** @var int $questiontime question time for the question */
    protected $questiontime;

    /** @var int $tries The number of tries */
    protected $tries;

    /** @var float $points The number of points for the question */
    protected $points;

    /** @var int $showhistoryduringquiz Whether or not to show the history of responses for a student during the quiz
     *                                  This will show the history table with each of the student's steps
     */
    protected $showhistoryduringquiz;

    /** @var object $question the question object from the question bank questions */
    protected $question;

    /** @var int $slot The quba slot that this question belongs to during page runtime
     *                  This is used during getting questions for the quizdata callback
     */
    protected $slot;


    /**
     * Construct the question
     *
     * @param int    $rtqqid
     * @param  int   $notime
     * @param int    $questiontime
     * @param int    $tries
     * @param float  $points
     * @param int    $showhistoryduringquiz
     * @param object $question
     */
    public function __construct($rtqqid, $notime, $questiontime, $tries, $points, $showhistoryduringquiz, $question) {
        $this->id = $rtqqid;
        $this->notime = $notime;
        $this->questiontime = $questiontime;
        $this->tries = $tries;
        $this->points = $points;
        $this->showhistoryduringquiz = $showhistoryduringquiz;
        $this->question = $question;

        $this->slot = null;
    }

    /**
     * not used function until we only support 5.4 and higher
     */
    public function JsonSerialize() {
        // to make sue of the is function on json_encode, this class also needs to implement JsonSerializable

        // TODO: This will be supported if Moodle moves to only supporting php 5.4 and higher

    }

    /**
     * Returns the activequiz id
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the notime field which is whether or not this question has time
     *
     * @return int
     */
    public function getNoTime() {
        return $this->notime;
    }

    /**
     * Returns the question time for this realtime quiz question
     *
     * @return int
     */
    public function getQuestionTime() {
        return $this->questiontime;
    }

    /**
     * Returns the number of tries for the question
     *
     * @return int
     */
    public function getTries() {
        return $this->tries;
    }

    /**
     * Returns the number of points for the question
     *
     * @return float
     */
    public function getPoints() {
        return $this->points;
    }

    /**
     * Returns whether or not to show the history during quiz
     *
     * @return int
     */
    public function getShowHistory() {
        return $this->showhistoryduringquiz;
    }

    /**
     * Returns the standard class question object from the question table
     *
     * @return \stdClass
     */
    public function getQuestion() {
        return $this->question;
    }

    /**
     * Sets the slot number
     *
     * @param int $slot
     */
    public function set_slot($slot) {
        $this->slot = $slot;
    }

    /**
     * returns the current slot number
     *
     * @return int
     */
    public function get_slot() {
        return $this->slot;
    }


}

