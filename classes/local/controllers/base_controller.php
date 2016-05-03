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

namespace mod_activequiz\local\controllers;

use mod_activequiz\local\activequiz_container;

defined('MOODLE_INTERNAL') || die();

abstract class base_controller {

    /*** @var activequiz_container $container The container instance for the controller */
    protected $container;

    /** @var string $action The specified action to take. */
    protected $action;

    /** @var object $context The specific context for this activity. */
    protected $context;

    /** @var \moodle_url $pageurl The page url to base other calls on. */
    protected $pageurl;

    /** @var array $this ->pagevars An array of page options for the page load. */
    protected $pagevars;


    public function __construct() {

        $this->container = activequiz_container::getInstance();
    }

    /**
     * Initializes an active quiz controller.
     * 
     * @param $baseurl
     * @throws \coding_exception
     */
    protected function initialize($baseurl) {
        global $PAGE, $CFG, $DB;

        $this->pagevars = array();

        $this->pageurl = new \moodle_url($baseurl);
        $this->pageurl->remove_all_params();

        $id = optional_param('cmid', false, PARAM_INT);
        $quizid = optional_param('quizid', false, PARAM_INT);

        // get necessary records from the DB.
        if ($id) {
            $this->container->coursemodule = get_coursemodule_from_id('activequiz', $id, 0, false, MUST_EXIST);
            $this->container->course = $DB->get_record('course', array('id' => $this->container->coursemodule->course), '*', MUST_EXIST);
            $this->quizrecord = $DB->get_record('activequiz', array('id' => $this->container->coursemodule->instance), '*', MUST_EXIST);
        } else {
            $this->quizrecord = $DB->get_record('activequiz', array('id' => $quizid), '*', MUST_EXIST);
            $this->course = $DB->get_record('course', array('id' => $this->quizrecord->course), '*', MUST_EXIST);
            $this->coursemodule = get_coursemodule_from_instance('activequiz', $this->quizrecord->id, $this->course->id, false, MUST_EXIST);
        }
        $this->get_parameters(); // get the rest of the parameters and set them in the class.

        $this->container->context = \context_module::instance($this->coursemodule->id);
        
    }
    
    public function get_parameters() {

        $this->action = optional_param('action', 'listquestions', PARAM_ALPHA);
    }

    /**
     * Children classes must define this action to be called.
     * 
     * @return mixed
     */
    abstract function defaultAction();
}
