<?php

namespace mod_activequiz\reports;

/**
 * Interface ireport
 * @package mod_activequiz\reports
 * @author John Hoopes
 * @copyright 2015 John Hoopes
 */
interface ireport {


    public function __construct(\mod_activequiz\activequiz $activequiz);

    /**
     * @param \moodle_url $pageurl
     * @param array $pagevars
     * @return mixed
     */
    public function handle_request($pageurl, $pagevars);

}