<?php
/**
 * This page lists all the instances of activequiz in a particular course
 *
 * @author: Davosmith
 * @package activequiz
 **/

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // course

if (!$course = $DB->get_record('course', array('id' => $id))) {
    error("Course ID is incorrect");
}

$PAGE->set_url(new moodle_url('/mod/activequiz/index.php', array('id' => $course->id)));
require_course_login($course);
$PAGE->set_pagelayout('incourse');

add_to_log($course->id, 'activequiz', 'view all', "index.php?id=$course->id", "");


/// Get all required strings

$stractivequizzes = get_string("modulenameplural", "activequiz");
$stractivequiz = get_string("modulename", "activequiz");

$PAGE->navbar->add($stractivequizzes);
$PAGE->set_title(strip_tags($course->shortname . ': ' . $stractivequizzes));
//$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

/// Get all the appropriate data

if (!$activequizs = get_all_instances_in_course("activequiz", $course)) {
    notice("There are no activequizes", "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname = get_string("name");
$strweek = get_string("week");
$strtopic = get_string("topic");

$table = new html_table();

if ($course->format == "weeks") {
    $table->head = array($strweek, $strname);
    $table->align = array("center", "left");
} else if ($course->format == "topics") {
    $table->head = array($strtopic, $strname);
    $table->align = array("center", "left");
} else {
    $table->head = array($strname);
    $table->align = array("left", "left");
}

foreach ($activequizs as $activequiz) {
    $url = new moodle_url('/mod/activequiz/view.php', array('id' => $activequiz->coursemodule));
    if (!$activequiz->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="' . $url . '">' . $activequiz->name . '</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="' . $url . '">' . $activequiz->name . '</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($activequiz->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);

/// Finish the page

echo $OUTPUT->footer();

