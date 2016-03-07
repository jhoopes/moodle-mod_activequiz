
# Active Quiz Changelog 

* 2015-3-6
    * "Regrade all grades" no forces the refresh of all question attempts.  
        * This is so that if you change the answer on a question, or other aspects of the question, clicking this button will re-run student's attempts
    * Points on a question can now be 0
    * Updating points on a question will now also auto regrade all attempts with the new maximum point value
    * Moving of many of the renderers to "sub-renderers" so that it's easier to maintain going forward
    * Initial structure so that reports are more "pluggable" so that new ones can be added in the future
    * Anonymization of Quiz responses (including fully anonymization of responses).  Many Thanks to Davo Smith @davosmith for help with this feature
    * Fix for enforcement of number of tries left on a quiz "resume" / refresh of the page.  (#39)
    * Fix for bug with tinyMCE not saving correctly when a question ends. (Thanks to @aolley for this fix)
    * Fix for bug with last question flag if there is only 1 question within the active quiz (Thanks to @aolley for this fix)
* 2015-12-03
    * Fixing sortablejs to not try to attach to moodle's AMD loader so that it will work, otherwise the plugin works as it should for 3.0
* 2015-07-22 
    * Adding anonymize responses for the instructor view during a quiz.  This feature does not anonymize for grading or student/group review
* 2015-07-08 
    * Fixing a missing strings issue, along with question category searching not working on Moodle 2.7 branch
* 2015-06-01
    * Fixing of missing CSS style "hidden" as some themes do not contain this style, also adding "standard_intro_elements"
             function call instead of "add_intro_editor"
* 2015-05-27 
    * Some small minor changes from Moodle.org for plugin reviewing
    * Code style updates.
* 2015-04-30 
    * Updates required to make the edit page work with the new question engine display classes.
    * Drag and drop of questions.
* 2015-01-05 
    * Re-written module for integration with the question engine, with many other updates.

### Realtime Quiz Changelog

* 2013-11-28 - More Moodle 2.6 compatibility fixes
* 2013-11-19 - Moodle 2.6 compatibility fixes
* 2013-07-30 - Fixed embedding images when first creating a question.
* 2013-01-10 - Fixed HTML Editor in Moodle 2.3
* 2013-01-06 - Backup and restore now available
* 2013-01-05 - Questions now use a standard Moodle form, so can include images, videos, etc.
* 2012-07-02 - Reports page can now show all user responses - thanks to Frankie Kam and Al Rachels for the original code
* 2012-01-13 - Minor tweak: questions with 'no correct answers' score 100% for everyone (not 0%) for statistical purposes
* 2012-01-12 - Now able to include questions with no correct answers (for 'surveys'). Note: mixing questions with answers
              and no answers will give incorrect statistics. Also added various minor fixes.
... lots of changes that were not recorded here - see https://github.com/davosmith/moodle-realtimequiz for details
* v0.8 (20/12/2008) - Fixed: deleting associated answers/submissions when deleting questions. Now able to restore realtime
                    quizzes from backups.
* v0.7 (15/11/2008) - NOT RELEASED. Now able to backup (but not restore) realtime quizzes.
* v0.6 (4/10/2008) - Made the client computer resend requests if nothing comes back from the server within 2 seconds
                   (should stop quiz from getting stuck in heavy network traffic). Moved the language files into the
                   same folder as the rest of the files.
* v0.5 (18/7/2008) - Fixed bug where '&' '<' '>' symbols in questions / answers would cause quiz to break.
* v0.4 (22/11/2007) - you can now have different times for each question (set to '0' for the default time set for the quiz)
* v0.3 - added individual scores for students, display total number of questions
* v0.2 - fixed 404 errors for non-admin logins
* v0.1 - initial release