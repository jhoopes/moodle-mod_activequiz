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

namespace mod_activequiz;

defined('MOODLE_INTERNAL') || die();

use mod_activequiz\qbanktypes\question_bank_add_to_rtq_action_column;

/**
 * Subclass of the question bank view class to change the way it works/looks
 *
 * @package     mod_activequiz
 * @author      John Hoopes <hoopes@wisc.edu>
 * @copyright   2014 University of Wisconsin - Madison
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activequiz_question_bank_view extends \question_bank_view {

    /**
     * Add to the known field types of the question bank view
     *
     * @return array
     */
    protected function known_field_types() {
        $types = parent::known_field_types();
        $types[] = new question_bank_add_to_rtq_action_column($this);

        return $types;
    }

    /**
     * Define the columns we want to be displayed on the question bank
     *
     * @return array
     */
    protected function wanted_columns() {
        return array('addtortqaction', 'qtype', 'questionname',
            'editaction', 'previewaction');
    }


    /**
     * Shows the question bank editing interface.
     *
     * The function also processes a number of actions:
     *
     * Actions affecting the question pool:
     * move           Moves a question to a different category
     * deleteselected Deletes the selected questions from the category
     * Other actions:
     * category      Chooses the category
     * displayoptions Sets display options
     */
    public function display($tabname, $page, $perpage, $cat,
                            $recurse, $showhidden, $showquestiontext) {
        global $PAGE, $OUTPUT;

        if ($this->process_actions_needing_ui()) {
            return;
        }
        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);
        // Category selection form
        echo $OUTPUT->heading(get_string('questionbank', 'question'), 2);
        array_unshift($this->searchconditions, new \core_question\bank\search\hidden_condition(!$showhidden));
        array_unshift($this->searchconditions, new \core_question\bank\search\category_condition(
            $cat, $recurse, $editcontexts, $this->baseurl, $this->course));
        $this->display_options_form($showquestiontext);

        // continues with list of questions
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl, $cat, $this->cm,
            $recurse, $page, $perpage, $showhidden, $showquestiontext,
            $this->contexts->having_cap('moodle/question:add'));
    }

    /**
     * generate an add to Active quiz url so that when clicked the question will be added to the quiz
     *
     * @param int $questionid
     *
     * @return \moodle_url Moodle url to add the question
     */
    public function add_to_rtq_url($questionid) {

        global $CFG;
        $params = $this->baseurl->params();
        $params['questionid'] = $questionid;
        $params['action'] = 'addquestion';
        $params['sesskey'] = sesskey();

        return new \moodle_url('/mod/activequiz/edit.php', $params);

    }


    /**
     * Override of the parent function to remove the "with selected" code since we removed the checkbox to "select" items
     *
     * Prints the table of questions in a category with interactions
     *
     * @param object $course The course object
     * @param int    $categoryid The id of the question category to be displayed
     * @param int    $cm The course module record if we are in the context of a particular module, 0 otherwise
     * @param int    $recurse This is 1 if subcategories should be included, 0 otherwise
     * @param int    $page The number of the page to be displayed
     * @param int    $perpage Number of questions to show per page
     * @param bool   $showhidden True if also hidden questions should be displayed
     * @param bool   $showquestiontext whether the text of each question should be shown in the list
     */
    protected function display_question_list($contexts, $pageurl, $categoryandcontext,
                                             $cm = null, $recurse = 1, $page = 0, $perpage = 100, $showhidden = false,
                                             $showquestiontext = false, $addcontexts = array()) {
        global $CFG, $DB, $OUTPUT;

        $category = $this->get_current_category($categoryandcontext);

        $cmoptions = new \stdClass();
        $cmoptions->hasattempts = !empty($this->quizhasattempts);

        $strselectall = get_string('selectall');
        $strselectnone = get_string('deselectall');
        $strdelete = get_string('delete');

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        $canadd = has_capability('moodle/question:add', $catcontext);
        $caneditall = has_capability('moodle/question:editall', $catcontext);
        $canuseall = has_capability('moodle/question:useall', $catcontext);
        $canmoveall = has_capability('moodle/question:moveall', $catcontext);

        $this->create_new_question_form($category, $canadd);

        $this->build_query();
        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            return;
        }
        $questions = $this->load_page_questions($page, $perpage);

        echo '<div class="categorypagingbarcontainer">';
        $pageing_url = new \moodle_url('edit.php');
        $r = $pageing_url->params($pageurl->params());
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $pageing_url);
        $pagingbar->pagevar = 'qpage';
        echo $OUTPUT->render($pagingbar);
        echo '</div>';

        echo '<form method="post" action="edit.php">';
        echo '<fieldset class="invisiblefieldset" style="display: block;">';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo \html_writer::input_hidden_params($pageurl);

        echo '<div class="categoryquestionscontainer">';
        $this->start_table();
        $rowcount = 0;
        foreach ($questions as $question) {
            $this->print_table_row($question, $rowcount);
            $rowcount += 1;
        }
        $this->end_table();
        echo "</div>\n";

        echo '<div class="categorypagingbarcontainer pagingbottom">';
        echo $OUTPUT->render($pagingbar);
        if ($totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
            if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
                $url = new \moodle_url('edit.php', array_merge($pageurl->params(), array('qperpage' => 1000)));
                $showall = '<a href="' . $url . '">' . get_string('showall', 'moodle', $totalnumber) . '</a>';
            } else {
                $url = new \moodle_url('edit.php', array_merge($pageurl->params(), array('qperpage' => DEFAULT_QUESTIONS_PER_PAGE)));
                $showall = '<a href="' . $url . '">' . get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE) . '</a>';
            }
            echo "<div class='paging'>$showall</div>";
        }
        echo '</div>';

        echo '</fieldset>';
        echo "</form>\n";
    }


}