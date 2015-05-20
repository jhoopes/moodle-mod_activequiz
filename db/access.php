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


$capabilities = array(

    // Can start a quiz and move on to the next question
    // NB: must have 'attempt' as well to be able to see the questions
    'mod/activequiz:control'         => array(

        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    // Can try to answer the quiz
    'mod/activequiz:attempt'         => array(

        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    // Can view own attempts
    'mod/activequiz:viewownattempts' => array(
        'captype'      => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    // Can see who gave what answer
    'mod/activequiz:seeresponses'    => array(

        'captype'      => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    // Can add / delete / update questions
    'mod/activequiz:editquestions'   => array(

        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy'       => array(
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    // Can add an instance of this module to a course
    'mod/activequiz:addinstance'     => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy'       => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    )
);

