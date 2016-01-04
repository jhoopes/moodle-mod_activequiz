Active Quiz
-------------

PLEASE NOTE:  This is a complete re-write of the realtime quiz module.
There is currently no upgrade path from the previous version,
so any existing questions in the previous realtime quiz module will
need to be moved to the question engine to be used.

Because of this, we determined that the plugin should be renamed so that both can exist,
so that people may migrate slowly to this plugin if desired.  This plugin is sufficiently
different in functionality and code structure from the current realtime quiz that also led
to having this be a "new" module vs an update.

The original plugin was written by Davo Smith, to which the University of Wisconsin - Madison
(funded by an educational innovation grant given to the Medical school) re-wrote the plugin to
use the question engine integration as well as a bunch of other new features.


This plugin is maintained by John Hoopes separately from the University.

What is it?
-----------
This is a type of quiz designed to be used in face-to-face lessons, with a classroom full of computers/tablets/phones.
The teacher creates the quiz in advance - adding question bank questions from the Moodle question bank.
(This includes any question type available for Moodle)

The quiz can be set up to take the quiz in class groups (set up through the Moodle built in groups functionality).
Group attendance may also be used in order only give present group members the final grade.

During the lesson, the teacher starts the quiz. Students can now connect to this quiz.
Once the teacher is satisfied that all students have connected to the quiz, they can click on "Start Quiz"
Depending on the question settings the question will end automatically, or will need to be ended via the instructor's
"end question" button.


The teacher can, at a later date, go back through the results and, for each question, see exactly what answer each
student gave.  Students will also be able to view their attempts based on the display options in the quiz settings

Changes:
--------
2016-01-04 - Anonymize option now anonymizes the data stored in the database - it is not available for review or to update the gradebook
2015-12-03 - Fixing sortablejs to not try to attach to moodle's AMD loader so that it will work, otherwise the plugin works as it should for 3.0
2015-07-22 - Adding anonymize responses for the instructor view during a quiz.  This feature does not anonymize for grading or student/group review
2015-07-08 - Fixing a missing strings issue, along with question category searching not working on Moodle 2.7 branch
2015-06-01 - Fixing of missing CSS style "hidden" as some themes do not contain this style, also adding "standard_intro_elements"
             function call instead of "add_intro_editor"
2015-05-27 - Some small minor changes from Moodle.org for plugin reviewing.  Also a large number of code style updates.
2015-04-30 - Updates required to make the edit page work with the new question engine display classes.
             Drag and drop of questions was also added.
2015-01-05 - Re-written module for integration with the question engine, with many other updates.

 -- Changes older than this belong to the original realtime quiz module --

2013-11-28 - More Moodle 2.6 compatibility fixes
2013-11-19 - Moodle 2.6 compatibility fixes
2013-07-30 - Fixed embedding images when first creating a question.
2013-01-10 - Fixed HTML Editor in Moodle 2.3
2013-01-06 - Backup and restore now available
2013-01-05 - Questions now use a standard Moodle form, so can include images, videos, etc.
2012-07-02 - Reports page can now show all user responses - thanks to Frankie Kam and Al Rachels for the original code
2012-01-13 - Minor tweak: questions with 'no correct answers' score 100% for everyone (not 0%) for statistical purposes
2012-01-12 - Now able to include questions with no correct answers (for 'surveys'). Note: mixing questions with answers
              and no answers will give incorrect statistics. Also added various minor fixes.
... lots of changes that were not recorded here - see https://github.com/davosmith/moodle-activequiz for details
v0.8 (20/12/2008) - Fixed: deleting associated answers/submissions when deleting questions. Now able to restore realtime
                    quizzes from backups.
v0.7 (15/11/2008) - NOT RELEASED. Now able to backup (but not restore) realtime quizzes.
v0.6 (4/10/2008) - Made the client computer resend requests if nothing comes back from the server within 2 seconds
                   (should stop quiz from getting stuck in heavy network traffic). Moved the language files into the
                   same folder as the rest of the files.
v0.5 (18/7/2008) - Fixed bug where '&' '<' '>' symbols in questions / answers would cause quiz to break.
v0.4 (22/11/2007) - you can now have different times for each question (set to '0' for the default time set for the quiz)
v0.3 - added individual scores for students, display total number of questions
v0.2 - fixed 404 errors for non-admin logins
v0.1 - initial release

Installation:
-------------
Unzip all the files into a temporary directory.
Copy the 'activequiz' folder into '<moodlehomedir>/mod'.
The system administrator should then log in to moodle and click on the 'Notifications' link in the Site administration
block.

Uninstalling:
-------------
Delete the module from the 'Activities' module list in the admin section.

Feedback:
---------

You can contact me (John Hoopes) at john DOT z DOT hoopes AT gmail DOT com
You can contact Davo Smith on 'moodle AT davosmith DOT co DOT uk, or at http://www.davosmith.co.uk/contact.php

This module is provided as is by the University of Wisconsin released under the same license of Moodle (GPL v3)
