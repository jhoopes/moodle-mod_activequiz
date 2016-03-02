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
global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table lib subclass for showing a session attempts
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sessionattempts extends \flexible_table implements \renderable {


    /** @var \mod_activequiz\activequiz $rtq */
    protected $rtq;

    /** @var \mod_activequiz\activequiz_session $session The session we're showing attempts for */
    protected $session;

    /**
     * Contstruct this table class
     *
     * @param string                             $uniqueid The unique id for the table
     * @param \mod_activequiz\activequiz         $rtq
     * @param \mod_activequiz\activequiz_session $session
     * @param \moodle_url                        $pageurl
     */
    public function __construct($uniqueid, $rtq, $session, $pageurl) {

        $this->rtq = $rtq;
        $this->session = $session;
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

        $columns = array(
            'fullname'     => get_string('name'),
            'attempt'      => get_string('attemptno', 'activequiz'),
            'preview'      => get_string('preview'),
            'timestart'    => get_string('startedon', 'activequiz'),
            'timefinish'   => get_string('timecompleted', 'activequiz'),
            'timemodified' => get_string('timemodified', 'activequiz'),
            'status'       => get_string('status'),
            'attemptgrade' => get_string('attempt_grade', 'activequiz'),
        );

        if (!$isdownloading) {
            $columns['edit'] = get_string('response_attempt_controls', 'activequiz');
        }

        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));

        //$this->sortable(true, 'timestart');
        $this->collapsible(true);

        $this->column_class('fullname', 'bold');
        $this->column_class('sumgrades', 'bold');

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

            if (!$download) {


                if ($item->userid > 0) {
                    $userlink = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$item->userid.
                            '&amp;course='.$this->rtq->getCourse()->id.'">';
                    $userlinkend = '</a>';
                } else {
                    $userlink = '';
                    $userlinkend = '';
                }

                if ($this->rtq->group_mode()) {

                    //$userlink = $item->groupname . ' (<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $item->userid .
                        //'&amp;course=' . $this->rtq->getCourse()->id . '">' . $item->takenby . '</a>)';

                    $userlink = $item->groupname . ' (' .$userlink . $item->takenby . $userlinkend . ')';

                } else {
                    //$userlink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $item->userid .
                        //'&amp;course=' . $this->rtq->getCourse()->id . '">' . $item->username . '</a>';

                    $userlink = $userlink . $item->username . $userlinkend;
                }
                $row[] = $userlink;
            } else {
                if ($this->rtq->group_mode()) {
                    $row [] = $item->groupname . ' (' . $item->takenby . ')';
                } else {
                    $row[] = $item->username;
                }
            }

            $row[] = $item->attemptno;
            $row[] = $item->preview;
            $row[] = date('m-d-Y H:i:s', $item->timestart);
            if (!empty($item->timefinish)) {
                $row[] = date('m-d-Y H:i:s', $item->timefinish);
            } else {
                $row[] = ' - ';
            }
            $row[] = date('m-d-Y H:i:s', $item->timemodified);
            $row[] = $item->status;

            if (is_null($item->grade)) {
                $totalmark = ' - ';
            } else {
                $totalmark = $item->grade . ' / ' . $item->totalgrade;
            }
            $row[] = $totalmark;

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
        global $DB;


        $data = array();

        $attempts = $this->session->getall_attempts(true);
        $userids = array();
        foreach ($attempts as $attempt) {
            if ($attempt->userid > 0) {
                $userids[] = $attempt->userid;
            }
        }

        // get user records to get the full name
        if (!empty($userids)) {
            list($useridsql, $params) = $DB->get_in_or_equal($userids);
            $sql = 'SELECT * FROM {user} WHERE id ' . $useridsql;
            $userrecs = $DB->get_records_sql($sql, $params);
        } else {
            $userrecs = array();
        }

        foreach ($attempts as $attempt) {
            /** @var \mod_activequiz\activequiz_attempt $attempt */
            $ditem = new \stdClass();
            $ditem->attemptid = $attempt->id;
            $ditem->sessionid = $attempt->sessionid;

            if( isset($userrecs[$attempt->userid]) ) {
                $name = fullname($userrecs[$attempt->userid]);
                $userid = $attempt->userid;
            }else {
                $name = get_string('anonymoususer', 'mod_activequiz');
                $userid = null;
            }

            if ($this->rtq->group_mode()) {

                $ditem->userid = $userid;
                $ditem->takenby = $name;
                $ditem->groupname = $this->rtq->get_groupmanager()->get_group_name($attempt->forgroupid);

            } else {
                $ditem->userid = $userid;
                $ditem->username = $name;
            }

            $ditem->attemptno = $attempt->attemptnum;
            $ditem->preview = $attempt->preview;
            $ditem->status = $attempt->getStatus();
            $ditem->timestart = $attempt->timestart;
            $ditem->timefinish = $attempt->timefinish;
            $ditem->timemodified = $attempt->timemodified;
            $ditem->grade = number_format($this->rtq->get_grader()->calculate_attempt_grade($attempt), 2);
            $ditem->totalgrade = $this->rtq->getRTQ()->scale;
            $data[ $attempt->id ] = $ditem;
        }

        return $data;
    }

}

