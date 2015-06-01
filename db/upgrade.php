<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_activequiz_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014112002) { // adding group attendance feature, custom points, and review options

        // Define field groupattendance to be added to activequiz.
        $table = new xmldb_table('activequiz');
        $field = new xmldb_field('groupattendance', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'workedingroups');

        // Conditionally launch add field groupattendance.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table activequiz_groupattendance to be created.
        $table = new xmldb_table('activequiz_groupattendance');

        // Adding fields to table activequiz_groupattendance.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('activequizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table activequiz_groupattendance.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for activequiz_groupattendance.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field points to be added to activequiz_questions.
        $table = new xmldb_table('activequiz_questions');
        $field = new xmldb_field('points', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '1.00', 'tries');

        // Conditionally launch add field points.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field reviewoptions to be added to activequiz.
        $table = new xmldb_table('activequiz');
        $field = new xmldb_field('reviewoptions', XMLDB_TYPE_TEXT, null, null, null, null, null, 'grouping');

        // Conditionally launch add field reviewoptions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // activequiz savepoint reached.
        upgrade_mod_savepoint(true, 2014112002, 'activequiz');
    }

    if ($oldversion < 2015030300) {

        // Define field showhistoryduringquiz to be added to activequiz_questions.
        $table = new xmldb_table('activequiz_questions');
        $field = new xmldb_field('showhistoryduringquiz', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'points');

        // Conditionally launch add field showhistoryduringquiz.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('activequiz');
        $field = new xmldb_field('waitforquestiontime', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '5', 'defaultquestiontime');

        // Conditionally launch add field waitforquestiontime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // activequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015030300, 'activequiz');
    }

    return true;
}
