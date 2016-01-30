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
 * @copyright 2016 John Hoopes <john.z.hoopes@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_activequiz\traits\renderer_base;

defined('MOODLE_INTERNAL') || die();

class edit_renderer extends \plugin_renderer_base {

    use renderer_base;


    /**
     * Prints edit page header
     *
     */
    public function print_header() {

        $this->base_header('edit');
        echo $this->output->box_start('generalbox boxaligncenter activequizbox');
    }

    /**
     * Render the list questions view for the edit page
     *
     * @param array  $questions Array of questions
     * @param string $questionbankview HTML for the question bank view
     */
    public function listquestions($questions, $questionbankview) {
        global $CFG;

        echo \html_writer::start_div('row', array('id' => 'questionrow'));

        echo \html_writer::start_div('span6');
        echo \html_writer::tag('h2', get_string('questionlist', 'activequiz'));
        echo \html_writer::div('', 'rtqstatusbox rtqhiddenstatus', array('id' => 'editstatus'));

        echo $this->show_questionlist($questions);

        echo \html_writer::end_div();

        echo \html_writer::start_div('span6');
        echo $questionbankview;
        echo \html_writer::end_div();

        echo \html_writer::end_div();

        $this->page->requires->js('/mod/activequiz/js/core.js');
        $this->page->requires->js('/mod/activequiz/js/sortable/sortable.min.js');
        $this->page->requires->js('/mod/activequiz/js/edit_quiz.js');

        // next set up a class to pass to js for js info
        $jsinfo = new \stdClass();
        $jsinfo->sesskey = sesskey();
        $jsinfo->siteroot = $CFG->wwwroot;
        $jsinfo->cmid = $this->activequiz->getCM()->id;

        // print jsinfo to javascript
        echo \html_writer::start_tag('script', array('type' => 'text/javascript'));
        echo "rtqinitinfo = " . json_encode($jsinfo);
        echo \html_writer::end_tag('script');

        $this->page->requires->strings_for_js(array(
            'success',
            'error'
        ), 'core');

    }


    /**
     * Builds the question list from the questions passed in
     *
     * @param array $questions an array of \mod_activequiz\activequiz_question
     * @return string
     */
    protected function show_questionlist($questions) {

        $return = '<ol class="questionlist">';
        $questioncount = count($questions);
        $questionnum = 1;
        foreach ($questions as $question) {
            /** @var \mod_activequiz\activequiz_question $question */
            $return .= '<li data-questionid="' . $question->getId() . '">';
            $return .= $this->display_question_block($question, $questionnum, $questioncount);
            $return .= '</li>';
            $questionnum++;
        }
        $return .= '</ol>';

        return $return;
    }

    /**
     * sets up what is displayed for each question on the edit quiz question listing
     *
     * @param \mod_activequiz\activequiz_question $question
     * @param int                                 $qnum The question number we're currently on
     * @param int                                 $qcount The total number of questions
     *
     * @return string
     */
    protected function display_question_block($question, $qnum, $qcount) {

        $return = '';

        $dragicon = new \pix_icon('i/dragdrop', 'dragdrop');
        $return .= \html_writer::div($this->output->render($dragicon), 'dragquestion');

        $return .= \html_writer::div(print_question_icon($question->getQuestion()), 'icon');

        $namehtml = \html_writer::start_tag('p');

        $namehtml .= $question->getQuestion()->name . '<br />';
        $namehtml .= get_string('points', 'activequiz') . ': ' . $question->getPoints();
        $namehtml .= \html_writer::end_tag('p');

        $return .= \html_writer::div($namehtml, 'name');

        $controlHTML = '';

        $spacericon = new \pix_icon('spacer', 'space', null, array('class' => 'smallicon space'));
        $controlHTML .= \html_writer::start_tag('noscript');
        if ($qnum > 1) { // if we're on a later question than the first one add the move up control

            $moveupurl = clone($this->pageurl);
            $moveupurl->param('action', 'moveup');
            $moveupurl->param('questionid', $question->getId()); // add the rtqqid so that the question manager handles the translation

            $alt = get_string('questionmoveup', 'mod_activequiz', $qnum);

            $upicon = new \pix_icon('t/up', $alt);
            $controlHTML .= \html_writer::link($moveupurl, $this->output->render($upicon));
        } else {
            $controlHTML .= $this->output->render($spacericon);
        }
        if ($qnum < $qcount) { // if we're not on the last question add the move down control

            $movedownurl = clone($this->pageurl);
            $movedownurl->param('action', 'movedown');
            $movedownurl->param('questionid', $question->getId());

            $alt = get_string('questionmovedown', 'mod_activequiz', $qnum);

            $downicon = new \pix_icon('t/down', $alt);
            $controlHTML .= \html_writer::link($movedownurl, $this->output->render($downicon));

        } else {
            $controlHTML .= $this->output->render($spacericon);
        }

        $controlHTML .= \html_writer::end_tag('noscript');

        // always add edit and delete icons
        $editurl = clone($this->pageurl);
        $editurl->param('action', 'editquestion');
        $editurl->param('rtqquestionid', $question->getId());
        $alt = get_string('questionedit', 'activequiz', $qnum);
        $deleteicon = new \pix_icon('t/edit', $alt);
        $controlHTML .= \html_writer::link($editurl, $this->output->render($deleteicon));


        $deleteurl = clone($this->pageurl);
        $deleteurl->param('action', 'deletequestion');
        $deleteurl->param('questionid', $question->getId());
        $alt = get_string('questiondelete', 'mod_activequiz', $qnum);
        $deleteicon = new \pix_icon('t/delete', $alt);
        $controlHTML .= \html_writer::link($deleteurl, $this->output->render($deleteicon));


        $return .= \html_writer::div($controlHTML, 'controls');

        return $return;

    }

    /**
     * renders the add question form
     *
     * @param moodleform $mform
     */
    public function addquestionform($mform) {

        echo $mform->display();

    }

    public function opensession(){

        echo \html_writer::tag('h3', get_string('editpage_opensession_error', 'activequiz'));

    }

    /**
     * Ends the edit page with the footer of Moodle
     *
     */
    public function footer() {

        echo $this->output->box_end();
        $this->base_footer();
    }


}