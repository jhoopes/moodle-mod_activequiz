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
 * Privacy Subsystem implementation for mod_forum.
 *
 * @package    mod_activequiz
 * @copyright  2021 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_activequiz\privacy;

use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\deletion_criteria;
use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the forum activity module.
 *
 * @copyright  2021 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider
{

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $items The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table('activequiz_groupattendance', [
            'activequizid' => 'privacy:metadata:activequiz_groupattendance:activequizid',
            'userid' => 'privacy:metadata:activequiz_groupattendance:userid',
            'attemptid' => 'privacy:metadata:activequiz_groupattendance:attemptid',
        ], 'privacy:metadata:activequiz_groupattendance');

        $items->add_database_table('activequiz_attempts', [
            'userid' => 'privacy:metadata:activequiz_attempts:userid',
            'attemptnum' => 'privacy:metadata:activequiz_attempts:attemptnum',
            'responded' => 'privacy:metadata:activequiz_attempts:responded',
            'responded_count' => 'privacy:metadata:activequiz_attempts:responded_count',
            'timestart' => 'privacy:metadata:activequiz_attempts:timestart',
            'timefinish' => 'privacy:metadata:activequiz_attempts:timefinish',
            'timemodified' => 'privacy:metadata:activequiz_attempts:timemodified',
        ], 'privacy:metadata:activequiz_attempts');

        $items->add_database_table('activequiz_grades', [
                'activequizid' => 'privacy:metadata:activequiz_grades:activequizid',
                'userid' => 'privacy:metadata:activequiz_grades:userid',
                'grade' => 'privacy:metadata:activequiz_grades:grade',
                'timemodified' => 'privacy:metadata:activequiz_grades:timemodified',
        ], 'privacy:metadata:activequiz_grades');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of forum, that is any forum where the user has made any post, rated any content, or has any preferences.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $params = [
            'modname'       => 'activequiz',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {activequiz} a ON a.id = cm.instance
                  JOIN {activequiz_groupattendance} g ON g.activequizid = a.id
                 WHERE g.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {activequiz} a ON a.id = cm.instance
                  JOIN {activequiz_sessions} s ON s.activequizid = a.id
                  JOIN {activequiz_attempts} at ON at.sessionid = s.id
                 WHERE at.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {activequiz} a ON a.id = cm.instance
                  JOIN {activequiz_grades} g ON g.activequizid = a.id
                 WHERE g.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
            'instanceid'    => $context->instanceid,
            'modulename'    => 'activequiz',
        ];

        $sql = "SELECT g.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {activequiz} a ON a.id = cm.instance
                  JOIN {activequiz_groupattendance} g ON g.activequizid = a.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT ats.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {activequiz} a ON a.id = cm.instance
                  JOIN {activequiz_sessions} s ON s.activequizid = a.id
                  JOIN {activequiz_attempts} ats ON ats.sessionid = s.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT g.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {activequiz} a ON a.id = cm.instance
                  JOIN {activequiz_grades} g ON g.activequizid = a.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            $parentclass = array();

            // Get records for user ID.
            $rows = $DB->get_records('activequiz_groupattendance', array('userid' => $userid));

            if (count($rows) > 0) {
                $i = 0;
                foreach ($rows as $row) {
                    $parentclass[$i]['activequizid'] = $row->activequizid;
                    $parentclass[$i]['userid'] = $row->userid;
                    $parentclass[$i]['attemptid'] = $row->attemptid;
                    $i++;
                }
            }

            $rows = $DB->get_records('activequiz_attempts', array('userid' => $userid));

            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $parentclass[$i]['userid'] = $row->userid;
                    $parentclass[$i]['attemptnum'] = $row->attemptnum;
                    $parentclass[$i]['responded'] = $row->responded;
                    $parentclass[$i]['responded_count'] = $row->responded_count;
                    $parentclass[$i]['timestart'] = $row->timestart;
                    $parentclass[$i]['timefinish'] = $row->timefinish;
                    $parentclass[$i]['timemodified'] = $row->timemodified;
                    $i++;
                }
            }

            $rows = $DB->get_records('activequiz_grades', array('userid' => $userid));

            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $parentclass[$i]['activequizid'] = $row->userid;
                    $parentclass[$i]['userid'] = $row->conflictid;
                    $parentclass[$i]['grade'] = $row->userid;
                    $parentclass[$i]['timemodified'] = $row->conflictid;
                    $i++;
                }
            }

            writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:activequiz', 'activequiz')],
                    (object) $parentclass);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module.
        if (!$cm = get_coursemodule_from_id('activequiz', $context->instanceid)) {
            return;
        }

        $activequizid = $cm->instance;

        $DB->delete_records('activequiz_groupattendance', ['activequizid' => $activequizid]);
        $DB->delete_records('activequiz_grades', ['activequizid' => $activequizid]);

        $sql = "DELETE
                  FROM {activequiz_attempts} aa
                  JOIN {activequiz_sessions} as ON as.id = aa.instance
                  WHERE as.activequizid = :activequizid";

        $DB->execute($sql, array('activequizid' => $activequizid));

    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;
        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $activequizid = $cm->instance;

            $DB->delete_records('activequiz_groupattendance', ['activequizid' => $activequizid, 'userid' => $userid]);
            $DB->delete_records('activequiz_grades', ['activequizid' => $activequizid, 'userid' => $userid]);

            $sql = "DELETE
                  FROM {activequiz_attempts} aa
                  JOIN {activequiz_sessions} as ON as.id = aa.instance
                  WHERE as.activequizid = :activequizid AND aa.userid = :userid";

            $DB->execute($sql, array('activequizid' => $activequizid, 'userid' => $userid));
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $users = $userlist->get_users();
        foreach ($users as $user) {
            // Create contextlist.
            $contextlist = new approved_contextlist($user, 'mod_activequiz', array($context->id));
            // Call delete data.
            self::delete_data_for_user($contextlist);
        }
    }
}
