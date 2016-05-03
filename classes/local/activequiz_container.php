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

namespace mod_activequiz\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Class activequiz_container singleton.
 * This is a singleton class to act as a simple container
 * for holding dependencies needed by various classes.
 *
 * @package     mod_activequiz\local
 * @property    \stdClass $coursemodule The course module record
 * @property    \stdClass $course The course record
 * @property    \context_module $context The context module.
 * @property     $activequiz The activequiz class
 */
class activequiz_container {


    /**
     * @var array $bindings an array of bindings for variables/maps sent into this class.
     */
    private $bindings;
    
    /**
     * @var activequiz_container The reference to the instance of this class.
     */
    private static $instance;

    /**
     * Returns the instance of this class.
     *
     * @return activequiz_container The instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function __get($name)
    {

        if( isset($name, $this->bindings) ) {
            return $this->bindings[$name];
        }else {
            throw new \coding_exception('invalid getting of variable from activequiz container');
        }

    }

    public function __set($name, $value)
    {
        // TODO: Need to eventually figure out validation here.

        $this->bindings[$name] = $value;
    }


    /**
     * Protected to prevent 'newing' up this class outside of our getInstance function
     */
    protected function __construct(){}
    
    /**
     * Prevent cloning
     *
     * @return void
     */
    private function __clone(){}

    /**
     * Prevents un-serialization
     *
     * @return void
     */
    private function __wakeup(){}

}