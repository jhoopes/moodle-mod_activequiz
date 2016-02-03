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

namespace mod_activequiz\qbanktypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Custom action column for adding a question to the realtime quiz from the question bank view
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_add_to_rtq_action_column extends \core_question\bank\action_column_base {

    protected $stradd;

    public function init() {
        parent::init();
        $this->stradd = get_string('addtoquiz', 'activequiz');
    }

    public function get_name() {
        return 'addtortqaction';
    }

    protected function display_content($question, $rowclasses) {
        if (!question_has_capability_on($question, 'use')) {
            return;
        }
        $this->print_icon('t/add', $this->stradd, $this->qbank->add_to_rtq_url($question->id));
    }

    public function get_required_fields() {
        return array('q.id');
    }


}

