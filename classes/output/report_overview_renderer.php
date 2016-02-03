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

class report_overview_renderer extends \plugin_renderer_base {


    use renderer_base;

    /**
     * renders and echos the home page fore the responses section
     *
     * @param array      $sessions
     * @param string|int $selectedid
     */
    public function select_session($sessions, $selectedid = '') {


        $output = '';

        $selectsession = \html_writer::start_div('');
        $selectsession .= \html_writer::tag('h3', get_string('selectsession', 'activequiz'), array('class' => 'inline-block'));

        $sessionselecturl = clone($this->pageurl);
        $sessionselecturl->param('action', 'viewsession');

        $sessionoptions = array();
        foreach ($sessions as $session) {
            /** @var \mod_activequiz\activequiz_session $session */
            $sessionoptions[ $session->get_session()->id ] = $session->get_session()->name;
        }

        $sessionselect = new \single_select($sessionselecturl, 'sessionid', $sessionoptions, $selectedid);

        $selectsession .= \html_writer::div($this->output->render($sessionselect), 'inline-block');
        $selectsession .= \html_writer::end_div();

        $output .= $selectsession;

        $regradeurl = clone($this->pageurl);
        $regradeurl->param('action', 'regradeall');
        $regradeall = new \single_button($regradeurl, get_string('regradeallgrades', 'activequiz'), 'GET');
        $output .= \html_writer::div($this->output->render($regradeall), '');

        $output = \html_writer::div($output, 'activequizbox');

        echo $output;

    }

    /**
     * Report home function.  is empty untill we need something on the home page
     *
     */
    public function home() {


        $gradestable = new \mod_activequiz\tableviews\overallgradesview('gradestable', $this->activequiz, $this->pageurl);

        echo \html_writer::start_div('activequizbox');

        echo \html_writer::tag('h3', get_string('activitygrades', 'activequiz'));

        $gradestable->setup();
        $gradestable->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $gradestable->set_data();
        $gradestable->finish_output();

        echo \html_writer::end_div();
    }

    /**
     * Renders the session attempts table
     *
     * @param \mod_activequiz\tableviews\sessionattempts $sessionattempts
     */
    public function view_session_attempts(\mod_activequiz\tableviews\sessionattempts $sessionattempts) {


        $sessionattempts->setup();
        $sessionattempts->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $sessionattempts->set_data();
        $sessionattempts->finish_output();

    }

}