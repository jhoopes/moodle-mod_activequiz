<?php
//
// Capability definitions for the activequiz module.
//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.

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

    if ($oldversion < 2015072200) {

        // Define field anonymizeresponses to be added to activequiz.
        $table = new xmldb_table('activequiz');
        $field = new xmldb_field('anonymizeresponses', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field anonymizeresponses.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Activequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015072200, 'activequiz');
    }

    if ($oldversion < 2016013100) {
        $table = new xmldb_table('activequiz_attempts');
        $field = new xmldb_field('questionengid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'attemptnum');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        // Activequiz savepoint reached.
        upgrade_mod_savepoint(true, 2016013100, 'activequiz');
    }

    if( $oldversion < 2016030601 ) {

        // Define field anonymizeresponses to be dropped from activequiz.
        $table = new xmldb_table('activequiz');
        $field = new xmldb_field('anonymizeresponses');

        // Conditionally launch drop field anonymizeresponses.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field anonymize_responses to be added to activequiz_sessions.
        $table = new xmldb_table('activequiz_sessions');
        $field = new xmldb_field('anonymize_responses', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'name');

        // Conditionally launch add field anonymize_responses.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field fully_anonymize to be added to activequiz_sessions.
        $table = new xmldb_table('activequiz_sessions');
        $field = new xmldb_field('fully_anonymize', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'anonymize_responses');

        // Conditionally launch add field fully_anonymize.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field responded_count to be added to activequiz_attempts.
        $table = new xmldb_table('activequiz_attempts');
        $field = new xmldb_field('responded_count', XMLDB_TYPE_INTEGER, '11', null, null, null, '0', 'responded');

        // Conditionally launch add field responded_count.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Activequiz savepoint reached.
        upgrade_mod_savepoint(true, 2016030601, 'activequiz');
    }

    return true;
}
