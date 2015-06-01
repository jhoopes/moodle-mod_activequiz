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
 * @author    John Hoopes <hoopes@wisc.edu>
 * @copyright 2014 Unviersity of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$module->version = 2015030900;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2013111800;  // Moodle 2.6 (or above)
$module->cron = 0;           // Period for cron to check this module (secs)
$module->component = 'mod_activequiz';
$module->maturity = MATURITY_BETA;
$module->release = '3.4.1 (Build: 2015030900)';

