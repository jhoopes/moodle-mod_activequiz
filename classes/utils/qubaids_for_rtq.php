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
 * A {@link qubaid_condition} for finding all the question usages belonging to
 * a particular quiz.
 *
 * Adapted from Quiz module's qubaids_for_quiz
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qubaids_for_rtq extends \qubaid_join {
    public function __construct($sessionid, $includepreviews = true, $onlyfinished = false) {
        $where = 'rtqa.sessionid = :sessionid';
        $params = array('sessionid' => $sessionid);

        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }

        if ($onlyfinished) {
            $where .= ' AND state == :statefinished';
            $params['statefinished'] = \mod_activequiz\activequiz_attempt::FINISHED;
        }

        parent::__construct('{activequiz_attempts} rtqa', 'rtqa.questionengid', $where, $params);
    }
}
