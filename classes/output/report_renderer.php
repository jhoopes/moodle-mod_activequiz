<?php
namespace mod_activequiz\output;

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
 * Renderer outputting the quiz editing UI.
 *
 * @package mod_activequiz
 * @copyright 2015 John Hoopes <john.z.hoopes@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_activequiz\traits\renderer_base;

defined('MOODLE_INTERNAL') || die();

class report_renderer extends \plugin_renderer_base {


    use renderer_base;

    public function report_header() {

        $this->base_header('reports');

        $options = [
            'overview' => 'Overview Report'
        ];

        echo $this->output->single_select($this->pageurl, 'report_type', $options, $this->pagevars['report_type']);

    }

    /**
     * Basic footer for the responses page
     *
     */
    public function report_footer() {
        $this->base_footer();
    }

}