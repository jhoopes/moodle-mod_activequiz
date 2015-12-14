<?php

namespace mod_activequiz\reports;

/**
 * Class activequiz_report_base
 *
 *
 * @package mod_activequiz\reports
 * @author John Hoopes
 * @copyright 2015 John Hoopes
 */
class activequiz_report_base {


    /**
     * @var \mod_activequiz\activequiz $active quiz
     */
    protected $activequiz;


    public function __construct(\mod_activequiz\activequiz $activequiz)
    {
        $this->activequiz = $activequiz;
    }




}