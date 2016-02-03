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
 * Table lib subclass for showing the overall grades for a realtime quiz activity
 *
 * @package     mod_activequiz
 * @author      John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overallgradesview extends \flexible_table implements \renderable {


    /** @var \mod_activequiz\activequiz $activequiz */
    protected $activequiz;

    /**
     * Contstruct this table class
     *
     * @param string                     $uniqueid The unique id for the table
     * @param \mod_activequiz\activequiz $activequiz
     * @param \moodle_url                $pageurl
     */
    public function __construct($uniqueid, $activequiz, $pageurl) {

        $this->rtq = $activequiz;
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
                'fullname'     => get_string('name'),
                'group'        => get_string('groupmembership', 'activequiz'),
                'grade'        => get_string('grade'),
                'timemodified' => get_string('timemodified', 'activequiz'),
            );
        } else {
            $columns = array(
                'fullname'     => get_string('name'),
                'grade'        => get_string('grade'),
                'timemodified' => get_string('timemodified', 'activequiz'),
            );
        }

        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));

        $this->sortable(true);
        $this->collapsible(true);

        $this->column_class('fullname', 'bold');
        $this->column_class('grade', 'bold');

        $this->set_attribute('cellspacing', '0');
        $this->set_attribute('cellpadding', '2');
        $this->set_attribute('id', 'grades');
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

            $row[] = $item->fullname;
            if ($this->rtq->group_mode()) {
                $row[] = $item->group;
            }
            $row[] = $item->grade;
            $row[] = date('m-d-Y H:i:s', $item->timemodified);

            $this->add_data($row);
        }

    }


    /**
     * Gets the data for the table
     *
     * @return array $data The array of data to show
     */
    protected function get_data() {
        global $DB, $CFG;


        $data = array();


        $grades = \mod_activequiz\utils\grade::get_user_grade($this->rtq->getRTQ());


        $userids = array();
        foreach ($grades as $grade) {
            $userids[] = $grade->userid;
        }

        // get user records to get the full name
        if (!empty($userids)) {
            list($useridsql, $params) = $DB->get_in_or_equal($userids);
            $sql = 'SELECT * FROM {user} WHERE id ' . $useridsql;
            $userrecs = $DB->get_records_sql($sql, $params);
        } else {
            $userrecs = array();
        }

        foreach ($grades as $grade) {

            // check to see if the grade is for a gradebook role.  if not then their grade
            // shouldn't show up here
            $add = false;
            if ($roles = get_user_roles($this->rtq->getContext(), $grade->userid)) {
                $gradebookroles = explode(',', $CFG->gradebookroles);
                foreach ($roles as $role) {

                    if (in_array($role->roleid, $gradebookroles)) {
                        // if they have at least one gradebook role show their grade
                        $add = true;
                    }
                }
                if ($add === false) {
                    // don't show grade for non gradebook role
                    continue;
                }
            } else {
                // if there are no given roles for the context, then they aren't students
                continue;
            }

            $gradedata = new \stdClass();
            $gradedata->fullname = fullname($userrecs[ $grade->userid ]);
            if ($this->rtq->group_mode()) {

                $groups = $this->rtq->get_groupmanager()->get_user_groups($grade->userid);
                if (!empty($groups)) {
                    $groupstring = '';
                    foreach ($groups as $group) {
                        if (strlen($groupstring) > 0) {
                            // add a comma space if we're back in the foreach for a second or more time
                            $groupstring .= ', ';
                        }
                        $groupstring .= $this->rtq->get_groupmanager()->get_group_name($group->id);
                    }
                    $gradedata->group = $groupstring;
                } else {
                    $gradedata->group = ' - ';
                }
            }

            $gradedata->grade = number_format($grade->rawgrade, 2);
            $gradedata->timemodified = $grade->dategraded;

            $data[] = $gradedata;
        }

        return $data;
    }
}