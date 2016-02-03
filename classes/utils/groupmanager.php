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
 * Group manager class for activequiz
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groupmanager {


    /** @var \mod_activequiz\activequiz $rtq */
    protected $rtq;


    /**
     * Construct new instance
     *
     * @param \mod_activequiz\activequiz
     */
    public function __construct($rtq) {
        $this->rtq = $rtq;

    }


    /**
     *
     *
     * @param int|null $userid
     *
     * @return array An array of group objects keyed by groupid
     */
    public function get_user_groups($userid = null) {
        global $USER;

        // assume current user when none specified
        if (empty($userid)) {
            $userid = $USER->id;
        }

        if (!empty($this->rtq->getRTQ()->grouping)) {
            return groups_get_all_groups($this->rtq->getCourse()->id, $userid, $this->rtq->getRTQ()->grouping);
        } else {
            return array(); // return empty array when there is no grouping
        }
    }

    /**
     * Gets an array of group names keyed by their group id.  is useful for selects and simple foreaches
     *
     * @param int|null $userid If left empty, current user is assumed
     * @param bool     $withdots Whether or not to have the choosedots string be the first element in the array
     * @return array An array of group names keyed by their id
     */
    public function get_user_groups_name_array($userid = null, $withdots = false) {

        $groups = $this->get_user_groups($userid);
        $retgroups = array();

        if ($withdots) {
            $retgroups[''] = get_string('choosedots');
        }

        foreach ($groups as $group) {
            $retgroups[ $group->id ] = $group->name;
        }

        return $retgroups;
    }

    /**
     * Get the group name for the specified groupid
     *
     * @param int $groupid The groupid
     *
     * @return string
     */
    public function get_group_name($groupid) {
        return groups_get_group_name($groupid);
    }

    /**
     * Wrapper function to get the group members
     *
     * @param int $groupid The groupid to get the members of
     *
     * @return array An array of user table user objects
     */
    public function get_group_members($groupid) {
        return groups_get_members($groupid);
    }

    /**
     * Wrapper function for groups is member
     *
     * @param int $groupid
     * @param int $userid Can be left blank and will assume current user if so
     *
     * @return bool
     */
    public function is_member_of_group($groupid, $userid = null) {
        return groups_is_member($groupid, $userid);
    }

    /**
     * Get a list of groups based on the groupids passed in
     *
     * @param array $groups An array of groupids
     *
     * @return array An array of group objects
     */
    public function get_groups($groups) {


    }

    /**
     * Get the group attendance for the specified field values
     *
     * If no field values are specified an empty array is returned
     *
     * @param int $activequizid
     * @param int $sessionid
     * @param int $attemptid
     * @param int $groupid
     *
     * @return array
     */
    public function get_attendance($activequizid = null, $sessionid = null, $attemptid = null, $groupid = null) {
        global $DB;

        $conditions = array();

        if (!empty($activequizid)) {
            $conditions['activequizid'] = $activequizid;
        }
        if (!empty($sessionid)) {
            $conditions['sessionid'] = $sessionid;
        }
        if (!empty($attemptid)) {
            $conditions['attemptid'] = $attemptid;
        }
        if (!empty($groupid)) {
            $conditions['groupid'] = $groupid;
        }

        if (empty($conditions)) {
            // if no conditions return empty array
            return array();
        }

        return $DB->get_records('activequiz_groupattendance', $conditions);

    }

}

