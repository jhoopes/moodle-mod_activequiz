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

namespace mod_activequiz\tableviews;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 *
 *
 * @package   mod_realtimquiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ownattempts extends \flexible_table {


    /** @var \mod_activequiz\activequiz $rtq */
    protected $rtq;

    /**
     * Contstruct this table class
     *
     * @param string                     $uniqueid The unique id for the table
     * @param \mod_activequiz\activequiz $rtq
     * @param \moodle_url                $pageurl
     */
    public function __construct($uniqueid, $rtq, $pageurl) {

        $this->rtq = $rtq;
        $this->baseurl = $pageurl;

        parent::__construct($uniqueid);
    }


    /**
     * Setup the table, i.e. table headers
     *
     */
    public function setup() {
        // Set var for is downloading
        $isdownloading = $this->is_downloading();

        $this->set_attribute('cellspacing', '0');

        if ($this->rtq->group_mode()) {
            $columns = array(
                'session'    => get_string('sessionname', 'activequiz'),
                'group'      => get_string('group'),
                'timestart'  => get_string('startedon', 'activequiz'),
                'timefinish' => get_string('timecompleted', 'activequiz'),
                'grade'      => get_string('grade'),
            );
        } else {
            $columns = array(
                'session'    => get_string('sessionname', 'activequiz'),
                'timestart'  => get_string('startedon', 'activequiz'),
                'timefinish' => get_string('timecompleted', 'activequiz'),
                'grade'      => get_string('grade'),
            );
        }


        if (!$isdownloading) {
            $columns['attemptview'] = get_string('attemptview', 'activequiz');
        }

        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));

        $this->sortable(false);
        $this->collapsible(false);

        $this->column_class('session', 'bold');

        $this->set_attribute('cellspacing', '0');
        $this->set_attribute('cellpadding', '2');
        $this->set_attribute('id', 'attempts');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->set_attribute('align', 'center');

        parent::setup();
    }


    /**
     * Sets the data to the table
     *
     */
    public function set_data() {
        global $CFG, $OUTPUT;

        $download = $this->is_downloading();
        $tabledata = $this->get_data();

        foreach ($tabledata as $item) {

            $row = array();

            $row[] = $item->sessionname;
            if ($this->rtq->group_mode()) {
                $row[] = $item->group;
            }
            $row[] = date('m-d-Y H:i:s', $item->timestart);
            $row[] = date('m-d-Y H:i:s', $item->timefinish);
            $row[] = $item->grade . ' / ' . $item->totalgrade;

            // Add in controls column

            // view attempt
            $viewattempturl = new \moodle_url('/mod/activequiz/viewquizattempt.php');
            $viewattempturl->param('quizid', $this->rtq->getRTQ()->id);
            $viewattempturl->param('sessionid', $item->sessionid);
            $viewattempturl->param('attemptid', $item->attemptid);

            $viewattemptpix = new \pix_icon('t/preview', 'preview');
            $popup = new \popup_action('click', $viewattempturl, 'viewquizattempt');

            $actionlink = new \action_link($viewattempturl, '', $popup, array('target' => '_blank'), $viewattemptpix);

            $row[] = $OUTPUT->render($actionlink);

            $this->add_data($row);
        }

    }


    /**
     * Gets the data for the table
     *
     * @return array $data The array of data to show
     */
    protected function get_data() {
        global $DB, $USER;


        $data = array();

        $sessions = $this->rtq->get_sessions();

        foreach ($sessions as $session) {
            /** @var \mod_activequiz\activequiz_session $session */
            $sessionattempts = $session->getall_attempts(false, 'closed', $USER->id);

            foreach ($sessionattempts as $sattempt) {
                $ditem = new \stdClass();
                $ditem->attemptid = $sattempt->id;
                $ditem->sessionid = $sattempt->sessionid;
                $ditem->sessionname = $session->get_session()->name;
                if ($this->rtq->group_mode()) {
                    $ditem->group = $this->rtq->get_groupmanager()->get_group_name($sattempt->forgroupid);
                }
                $ditem->timestart = $sattempt->timestart;
                $ditem->timefinish = $sattempt->timefinish;
                $ditem->grade = number_format($this->rtq->get_grader()->calculate_attempt_grade($sattempt), 2);
                $ditem->totalgrade = $this->rtq->getRTQ()->scale;

                $data[ $sattempt->id ] = $ditem;
            }
        }

        return $data;
    }

}