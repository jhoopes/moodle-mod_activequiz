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
 * @package   mod_activequiz
 * @author    Andrew Hancox <andrewdchancox@googlemail.com>
 * @copyright 2015 Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/question/engine/bank.php');

if ($ADMIN->fulltree) {
    $choices = array();
    $defaults = array();
    foreach (question_bank::get_creatable_qtypes() as $qtypename => $qtype) {
        $fullpluginname = $qtype->plugin_name();
        $qtypepluginname = explode('_', $fullpluginname)[1];

        $choices[$qtypepluginname] = $qtype->menu_name();
        $defaults[$qtypepluginname] = 1;
    }
    $settings->add(new admin_setting_configmulticheckbox(
        'activequiz/enabledqtypes',
        get_string('enabledquestiontypes', 'activequiz'),
        get_string('enabledquestiontypes_info', 'activequiz'),
        $defaults,
        $choices));
}