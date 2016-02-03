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
 * Class to define grade types for the module
 * Is used in multiple classes/functions
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scaletypes {

    /** Define grading scale types */
    const activequiz_FIRSTSESSION = 1;
    const REALTIMQUIZ_LASTSESSION = 2;
    const activequiz_SESSIONAVERAGE = 3;
    const activequiz_HIGHESTSESSIONGRADE = 4;


    /**
     * Return array of scale types keyed by the type name
     *
     * @return array
     */
    public static function get_types() {

        return array(
            'firstattempt' => self::activequiz_FIRSTSESSION,
            'lastattempt'  => self::REALTIMQUIZ_LASTSESSION,
            'average'      => self::activequiz_SESSIONAVERAGE,
            'highestgrade' => self::activequiz_HIGHESTSESSIONGRADE,
        );
    }

    /**
     * Returns an array of scale types for display, i.e. a form
     * keyed by the values that each type is
     *
     * @return array
     */
    public static function get_display_types() {

        return array(
            self::activequiz_FIRSTSESSION        => get_string('firstsession', 'activequiz'),
            self::REALTIMQUIZ_LASTSESSION        => get_string('lastsession', 'activequiz'),
            self::activequiz_SESSIONAVERAGE      => get_string('sessionaverage', 'activequiz'),
            self::activequiz_HIGHESTSESSIONGRADE => get_string('highestsessiongrade', 'activequiz'),
        );
    }

}


