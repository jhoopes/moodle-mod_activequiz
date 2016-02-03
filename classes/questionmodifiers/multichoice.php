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

namespace mod_activequiz\questionmodifiers;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');

/**
 * Multiple choice question modifier class
 *
 * @package   mod_activequiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class multichoice implements \mod_activequiz\questionmodifiers\ibasequestionmodifier {


    public function requires_jquery() {
    }

    public function add_css() {
    }

    /**
     * Add chart.js to the page
     *
     */
    public function add_js() {
        global $PAGE;

        $PAGE->requires->js('/mod/activequiz/js/chartjs/Chart.min.js');

    }

    /**
     * Updating output to include a graph of multiple choice answer possibilities
     * with the percentage of students that answered that option
     *
     * @param \mod_activequiz\activequiz_question $question The realtime quiz question
     * @param array                               $attempts An array of \mod_activequiz\activequiz_attempt classes
     * @param string                              $output The current output from getting the results
     * @return string Return the updated output to be passed to the client
     */
    public function modify_questionresults_duringquiz($question, $attempts, $output) {
        global $DB;


        // store the possible answersid as the key of the array, and then a count
        //  for the number of times it was answered
        $answers = array();

        $dbanswers = array();


        foreach ($attempts as $attempt) {
            /** @var \mod_activequiz\activequiz_attempt $attempt */

            // only count attempts where they have "responded"
            if ($attempt->responded == 0) {
                continue;
            }

            $quba = $attempt->get_quba();
            $slot = $attempt->get_question_slot($question);

            $qa = $quba->get_question_attempt($slot);

            // now get question definition
            $questiondef = $qa->get_question();

            // if dbanswers is empty get them from the question definition (as this will be the same for all attempts for this slot
            // also save a db query
            if (empty($dbanswers)) {
                $dbanswers = $questiondef->answers;
            }

            // single and multi answers are handled differently for steps
            if ($questiondef instanceof \qtype_multichoice_single_question) {
                $this->update_answers_single($answers, $qa, $questiondef);
            } else if ($questiondef instanceof \qtype_multichoice_multi_question) {
                $this->update_answers_multi($answers, $qa, $questiondef);
            } else {

            }
        }
        $xaxis = array();
        foreach ($dbanswers as $dbanswer) {
            $xaxis[ $dbanswer->id ] = \question_utils::to_plain_text($dbanswer->answer, $dbanswer->answerformat);
        }

        $newoutput = $this->add_chart($output, $xaxis, $answers);

        return $newoutput;
    }


    /**
     *
     *
     * @param array                              $answers The overall answers array that handles the count of answers
     * @param \question_attempt                  $qa
     * @param \qtype_multichoice_single_question $questiondef
     *
     */
    protected function update_answers_single(&$answers, $qa, $questiondef) {


        // get the latest step that has an answer
        $lastanswerstep = $qa->get_last_step_with_qt_var('answer');

        if ($lastanswerstep->has_qt_var('answer')) {
            // may not as if the step doesn't exist get last step will return empty read only step

            // get the student answer from the last answer step
            // along with the answers within the
            $answerorder = $questiondef->get_order($qa);
            $response = $lastanswerstep->get_qt_data();

            // make sure the response answer actually exists in the order
            if (array_key_exists($response['answer'], $answerorder)) {
                $studentanswer = $questiondef->answers[ $answerorder[ $response['answer'] ] ];

                // update the count of the answerid on the answers array
                if (isset($answers[ $studentanswer->id ])) {
                    $current = (int)$answers[ $studentanswer->id ];
                    $answers[ $studentanswer->id ] = $current + 1;
                } else {
                    $answers[ $studentanswer->id ] = 1;
                }
            }
        }
    }

    /**
     *
     *
     * @param array                             $answers The overall answers array that handles the count of answers
     * @param \question_attempt                 $qa
     * @param \qtype_multichoice_multi_question $questiondef
     */
    protected function update_answers_multi(&$answers, $qa, $questiondef) {

        // get the order first so we can get the field ids
        $answerorder = $questiondef->get_order($qa);

        // make sure there are options, if so, look for the last step with choice0
        if (count($answerorder) > 0) {

            $lastanswerstep = $qa->get_last_step_with_qt_var('choice0');

            if ($lastanswerstep->has_qt_var('choice0')) {
                // may not as if the step doesn't exist get last step will return empty read only step
                $response = $lastanswerstep->get_qt_data();

                // next loop through the order to check if the 'choice' . $key are equal to 1
                // (signifies that the student answered with that answer)
                foreach ($answerorder as $key => $ansid) {
                    if (!empty($response[ 'choice' . $key ])) {
                        // update the count of the answerid on the answers array
                        if (isset($answers[ $ansid ])) {
                            $current = (int)$answers[ $ansid ];
                            $answers[ $ansid ] = $current + 1;
                        } else {
                            $answers[ $ansid ] = 1;
                        }
                    }
                }
            }
        }
    }


    /**
     *
     *
     * @param string $output The current output defined by
     * @param array  $xaxis an array of answer text values keyed by their answer id
     * @param array  $answers an array of answer count keyed by the answer id
     *
     * @return string
     */
    protected function add_chart($output, $xaxis, $answers) {


        $totalanswers = 0;
        foreach ($answers as $answercount) {
            $totalanswers = $totalanswers + $answercount;
        }

        // now set up chart vars to be then put into javascript
        $chartheight = 600;
        $chartwidth = 600;
        $labels = array();
        $percentagedatasetdata = array();
        $countdatasetdata = array();
        foreach ($xaxis as $ansid => $xaxisitem) {

            if (strlen($xaxisitem) > 30) {
                // if we have really long answers make the chart taller so that the chart section doesn't
                // get pushed up
                $chartheight = 800;
            }

            $labels[] = $xaxisitem;

            if (isset($answers[ $ansid ])) {
                // we have a value for this answer so get the count
                $anscount = $answers[ $ansid ];
            } else {
                $anscount = 0; // otherwise no one answered this option so it's 0
            }
            // set the data set values
            $countdatasetdata[] = $anscount;
            $percentagedatasetdata[] = $anscount / $totalanswers;
        }

        if (count($labels) > 6) {
            // if we have a lot of answers make the chart wider
            $chartwidth = 800;
        }

        $chartoutput = '';
        $chartoutput .= \html_writer::tag('canvas', '', array('id' => 'multichoicechart', 'width' => $chartwidth, 'height' => $chartheight));
        $chartoutput .= \html_writer::start_tag('script', array('type' => 'text/javascript', 'id' => 'multichoice_js'));

        $chartoutput .= '
            var ctx = document.getElementById("multichoicechart").getContext("2d");
        ';

        // create javascript vars to then json encode for the output

        $data = new \stdClass();
        $data->labels = $labels;
        $data->datasets = array();

        $countdataset = new \stdClass();
        $countdataset->label = get_string('countdatasetlabel', 'activequiz');
        $countdataset->fillColor = "#72E07A";
        $countdataset->strokeColor = "#549C59";
        $countdataset->highlightFill = "#B7EDBA";
        $countdataset->highlightStroke = "#98EB9E";
        $countdataset->data = $countdatasetdata;
        $data->datasets[] = $countdataset;

        $percentagedataset = new \stdClass();
        $percentagedataset->label = get_string('percentagedatasetlabel', 'activequiz');
        $percentagedataset->fillColor = "rgba(220,220,220,0.5)";
        $percentagedataset->strokeColor = "rgba(220,220,220,0.8)";
        $percentagedataset->highlightFill = "rgba(220,220,220,0.75)";
        $percentagedataset->highlightStroke = "rgba(220,220,220,1)";
        $percentagedataset->data = $percentagedatasetdata;

        $options = new \stdClass();
        $options->scaleBeginAtZero = true;
        $options->scaleShowGridLines = true;
        $options->scaleGridLineColor = "rgba(0,0,0,.05)";
        $options->scaleGridLineWidth = 1;
        $options->barShowStroke = true;
        $options->barStrokeWidth = 2;
        $options->barValueSpacing = 5;
        $options->barDatasetSpacing = 1;
        $options->legendTemplate = "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>";
        //$options->responsive = true;
        $options->scaleOverride = true;

        // set up scale values
        if ($totalanswers < 10) {
            $options->scaleSteps = 2;
        } else {

            $steps = ceil($totalanswers / 5);
            // always add one step for some padding
            $steps = $steps + 1;
            $options->scaleSteps = $steps;
        }
        $options->scaleStepWidth = 5;

        $chartoutput .= '
            var data = ' . json_encode($data) . ';
            var options = ' . json_encode($options) . ';
            var MultiChoiceChart = new Chart(ctx).Bar(data, options);
        ';
        $chartoutput .= \html_writer::end_tag('script');

        return $chartoutput . $output;
    }

}
