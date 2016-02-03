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
 * Local lib
 *
 * @package   mod_activequiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Prints local lib tabs
 *
 * @param \mod_activequiz\activequiz $RTQ Realtime quiz class
 * @param                            $currenttab
 *
 * @return string HTML string of the tabs
 */
function activequiz_view_tabs($RTQ, $currenttab) {
    $tabs = array();
    $row = array();
    $inactive = array();
    $activated = array();

    if ($RTQ->has_capability('mod/activequiz:attempt')) {
        $row[] = new tabobject('view', new moodle_url('/mod/activequiz/view.php', array('id' => $RTQ->getCM()->id)), get_string('view', 'activequiz'));
    }
    if ($RTQ->has_capability('mod/activequiz:editquestions')) {
        $row[] = new tabobject('edit', new moodle_url('/mod/activequiz/edit.php', array('cmid' => $RTQ->getCM()->id)), get_string('edit', 'activequiz'));
    }
    if ($RTQ->has_capability('mod/activequiz:seeresponses')) {
        $row[] = new tabobject('reports', new moodle_url('/mod/activequiz/reports.php', array('id' => $RTQ->getCM()->id)), get_string('responses', 'activequiz'));
    }

    if ($currenttab == 'view' && count($row) == 1) {
        // No tabs for students
        echo '<br />';
    } else {
        $tabs[] = $row;
    }

    if ($currenttab == 'reports') {
        $activated[] = 'reports';
    }

    if ($currenttab == 'edit') {
        $activated[] = 'edit';
    }

    if ($currenttab == 'view') {
        $activated[] = 'view';
    }

    return print_tabs($tabs, $currenttab, $inactive, $activated, true);
}

