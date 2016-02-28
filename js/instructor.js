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
 * @package   mod_activequiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ensure that the namespace is defined
var activequiz = activequiz || {};
activequiz.vars = activequiz.vars || {};

/**
 * The instructor's getQuizInfo function
 *
 * This function works to maintain instructor state as well as to assist in getting student responses
 * while the question is still running.  There are 2 variables that are set/get which are important
 *
 * "inquestion" signifies that the activequiz is in a question, and is updated in other functions to signify
 *              the end of a question
 * "endquestion" This variable is needed to help to keep the "inquestion" variable from being overwritten on the
 *               interval this function defines.  It is also updated by other functions in conjunction with "inquestion"
 *
 */
activequiz.getQuizInfo = function () {

    var params = {
        'sesskey': activequiz.get('sesskey'),
        'sessionid': activequiz.get('sessionid')
    };

    activequiz.ajax.create_request('/mod/activequiz/quizinfo.php', params, function (status, response) {

        if (status == 500) {
            alert('There was an error....' + response);
        } else if (status == 200) {
            if (response.status == 'notrunning') {
                // do nothing as we're not running
                activequiz.set('endquestion', 'false');

            } else if (response.status == 'running' && activequiz.get('inquestion') != 'true' && activequiz.get('endquestion') != 'true') {

                if (response.delay <= 0) {
                    // only set in question if we're in it, not waiting for it to start
                    activequiz.set('inquestion', 'true');
                }

            } else if (response.status == 'running' && activequiz.get('inquestion') != 'true') {

                // set endquestion to false as we're now "waiting" for a new question
                activequiz.set('endquestion', 'false');

            } else if (response.status == 'running' && activequiz.get('inquestion') == 'true') {

                // gether the current results
                if (activequiz.get('delayrefreshresults') === 'undefined' || activequiz.get('delayrefreshresults') === 'false') {
                    activequiz.gather_current_results();
                }

                // also get the students/groups not responded
                if (activequiz.get('shownotresponded') !== false) {
                    activequiz.getnotresponded();
                }

            } else if (response.status = 'endquestion') {

                activequiz.set('inquestion', 'false');

            } else if (response.status == 'reviewing') {

                activequiz.set('inquestion', 'false');

            } else if (response.status == 'sessionclosed') {

                activequiz.set('inquestion', 'false');

            }
        }

        var time = 3000 + Math.floor(Math.random() * (100 + 100) - 100);
        setTimeout(activequiz.getQuizInfo, time);

    });

};


activequiz.start_quiz = function () {

    // make an ajax callback to quizdata to start the quiz

    var params = {
        'action': 'startquiz',
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey')
    };

    this.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        // if there's only 1 question this will return true
        if (response.lastquestion == 'true') {
            // disable the next question button
            var nextquestionbtn = document.getElementById('nextquestion');
            nextquestionbtn.disabled = true;
            activequiz.set('lastquestion', 'true');
        }

        activequiz.waitfor_question(response.questionid, response.questiontime, response.delay, response.nextstarttime);
    });

    var startquizbtn = document.getElementById('startquiz');
    startquizbtn.classList.add('btn-hide');

    var inquizcontrols = document.getElementById('inquizcontrols');
    inquizcontrols.classList.remove('btn-hide');
    this.control_buttons(['endquestion', 'toggleresponses', 'togglenotresponded']);
};


activequiz.handle_question = function (questionid) {

    this.loading(M.util.get_string('gatheringresults', 'activequiz'), 'show');

    if (typeof tinyMCE !== 'undefined') {
        tinyMCE.triggerSave();
    }

    // will only work on Modern browsers
    // of course the problem child is always IE...
    var qform = document.forms.namedItem('q' + questionid);
    var formdata = new FormData(qform);

    formdata.append('action', 'savequestion');
    formdata.append('rtqid', activequiz.get('rtqid'));
    formdata.append('sessionid', activequiz.get('sessionid'));
    formdata.append('attemptid', activequiz.get('attemptid'));
    formdata.append('sesskey', activequiz.get('sesskey'));
    formdata.append('questionid', questionid);

    // submit the form
    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', formdata, function (status, response) {

        if (status == 500) {
            window.alert('there was an error with your request ... ' + response.error);
            return;
        }
        //update the sequence check for the question
        var sequencecheck = document.getElementsByName(response.seqcheckname);
        var field = sequencecheck[0];
        field.value = response.seqcheckval;


        // we don't really care about the response for instructors as we're going to set timeout
        // for gathering response

        activequiz.set('endquestion', 'true');
        activequiz.set('inquestion', 'false');

        var params = {
            'action': 'endquestion',
            'question': activequiz.get('currentquestion'),
            'rtqid': activequiz.get('rtqid'),
            'sessionid': activequiz.get('sessionid'),
            'attemptid': activequiz.get('attemptid'),
            'sesskey': activequiz.get('sesskey')
        };

        // make sure we end the question (on end_question function call this is re-doing what we just did)
        // but handle_request is also called on ending of the question timer in core.js
        activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

            if (status == 500) {
                var loadingbox = document.getElementById('loadingbox');
                loadingbox.classList.add('hidden');

                activequiz.quiz_info('There was an error with your request', true);
            } else if (status == 200) {

                var currentquestion = activequiz.get('currentquestion');
                var questiontimertext = document.getElementById('q' + currentquestion + '_questiontimetext');
                var questiontimer = document.getElementById('q' + currentquestion + '_questiontime');

                questiontimertext.innerHTML = '';
                questiontimer.innerHTML = '';

            }
        });

        setTimeout(activequiz.gather_results, 3500);
        setTimeout(activequiz.getnotresponded, 3500);

    });
};

/**
 * This function is slightly different than gather results as it doesn't look to alter the state of the quiz, or the interface
 * but just get the results of the quesiton and display them in the quiz info box
 *
 */
activequiz.gather_current_results = function () {


    if (activequiz.get('showstudentresponses') === false) {
        return; // return if there we aren't showing student responses
    }

    var params = {
        'action': 'getcurrentresults',
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey')
    };

    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        if (status == '500') {
            activequiz.quiz_info('there was an error getting current results', true);
        } else if (status == 200) {
            activequiz.quiz_info(response.responses, true);

            // after the responses have been inserted, we see if any question type javascript was added and evaluate
            if (document.getElementById(response.qtype + '_js') !== null) {
                eval(document.getElementById(response.qtype + '_js').innerHTML);
            }
        }

    });
};

/**
 * This function will call the normal getresults case of quiz data.  This alters the quiz state to "reviewing", as well as
 * updates the instructor's interface with the buttons allowed for this state of the quiz
 *
 */
activequiz.gather_results = function () {

    var params = {
        'action': 'getresults',
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey'),
    };

    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        activequiz.loading('', 'hide');

        var questionbox = document.getElementById('q' + activequiz.get('currentquestion') + '_container');
        questionbox.classList.remove('hidden');

        // don't change buttons during the question
        if (activequiz.get('endquestion') == 'true') {

            if (activequiz.get('lastquestion') != 'undefined') {

                if (activequiz.get('lastquestion') == 'true') { // don't enable the next question button

                    activequiz.control_buttons(['closesession', 'reloadresults', 'jumptoquestion', 'repollquestion', 'showcorrectanswer', 'toggleresponses']);
                } else {
                    //otherwise enable the next question button and repoll question

                    activequiz.control_buttons(['closesession', 'nextquestion', 'jumptoquestion', 'repollquestion', 'reloadresults', 'showcorrectanswer', 'toggleresponses']);
                }
            } else {
                activequiz.control_buttons(['closesession', 'nextquestion', 'jumptoquestion', 'repollquestion', 'reloadresults', 'showcorrectanswer', 'toggleresponses']);
            }
        }

        // only put results into the screen if
        if (activequiz.get('showstudentresponses') !== false) {

            activequiz.quiz_info(response.responses);

            // after the responses have been inserted, we see if any question type javascript was added and evaluate
            if (document.getElementById(response.qtype + '_js') !== null) {
                eval(document.getElementById(response.qtype + '_js').innerHTML);
            }
        }
    });

};

activequiz.reload_results = function () {

    this.hide_all_questionboxes();
    this.clear_and_hide_qinfobox();

    this.loading(M.util.get_string('gatheringresults', 'activequiz'), 'show');

    this.gather_results();
};

activequiz.repoll_question = function () {

    this.hide_all_questionboxes();
    this.clear_and_hide_qinfobox();
    this.control_buttons([]);

    // we want to send a request to re-poll the previous question, or the one we're reviewing now
    var params = {
        'action': 'repollquestion',
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey')
    };

    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        if (status == 500) {
            var loadingbox = document.getElementById('loadingbox');
            loadingbox.classList.add('hidden');

            activequiz.quiz_info('There was an error with your request', true);

            window.alert('there was an error with your request ... ');
            return;
        }

        if (response.lastquestion == 'true') {
            // set a var to signify this is the last question
            activequiz.set('lastquestion', 'true');
        } else {
            activequiz.set('lastquestion', 'false');
        }
        activequiz.control_buttons(['endquestion', 'toggleresponses', 'togglenotresponded']);
        activequiz.waitfor_question(response.questionid, response.questiontime, response.delay, response.nextstarttime);
    });

};

activequiz.next_question = function () {

    // hide all question boxes and disable certain buttons

    this.hide_all_questionboxes();
    this.clear_and_hide_qinfobox();
    this.control_buttons([]);

    // ensure that the previous question's form is hidden
    if (activequiz.get('currentquestion') != 'undefined') {
        var qformbox = document.getElementById('q' + activequiz.get('currentquestion') + '_container');
        qformbox.classList.add('hidden');
    }

    var params = {
        'action': 'nextquestion',
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey')
    };

    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        if (status == 500) {
            var loadingbox = document.getElementById('loadingbox');
            loadingbox.classList.add('hidden');

            activequiz.quiz_info('There was an error with your request', true);

            window.alert('there was an error with your request ... ');
            return;
        }

        if (response.lastquestion == 'true') {
            // set a var to signify this is the last question
            activequiz.set('lastquestion', 'true');
        } else {
            activequiz.set('lastquestion', 'false');
        }
        activequiz.control_buttons(['endquestion', 'toggleresponses', 'togglenotresponded']);
        activequiz.waitfor_question(response.questionid, response.questiontime, response.delay, response.nextstarttime);
    });
};

activequiz.end_question = function () {

    // we want to send a request to re-poll the previous question, or the one we're reviewing now
    var params = {
        'action': 'endquestion',
        'question': activequiz.get('currentquestion'),
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey')
    };

    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        if (status == 500) {
            var loadingbox = document.getElementById('loadingbox');
            loadingbox.classList.add('hidden');

            activequiz.quiz_info('There was an error with your request', true);

            window.alert('there was an error with your request ... ');
            return;
        }

        // clear the activequiz counter interval
        if (activequiz.qcounter) {
            clearInterval(activequiz.qcounter);
        }
        var currentquestion = activequiz.get('currentquestion');
        var questiontimertext = document.getElementById('q' + currentquestion + '_questiontimetext');
        var questiontimer = document.getElementById('q' + currentquestion + '_questiontime');

        questiontimertext.innerHTML = '';
        questiontimer.innerHTML = '';

        activequiz.set('inquestion', 'false'); // set inquestion to false as we've ended the question
        activequiz.set('endquestion', 'true');

        // after getting endquestion response, go through the normal handle_question flow
        activequiz.handle_question(activequiz.get('currentquestion'));
    });
};

activequiz.close_session = function () {

    activequiz.loading(M.util.get_string('closingsession', 'activequiz'), 'show');

    var params = {
        'action': 'closesession',
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey')
    };

    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        if (status == 500) {
            var loadingbox = document.getElementById('loadingbox');
            loadingbox.classList.add('hidden');

            activequiz.quiz_info('There was an error with your request', true);

            window.alert('there was an error with your request ... ');
            return;
        }

        activequiz.hide_all_questionboxes();
        activequiz.clear_and_hide_qinfobox();

        var controlsbox = document.getElementById('controlbox');
        controlsbox.classList.add('hidden');

        activequiz.quiz_info(M.util.get_string('sessionclosed', 'activequiz'));
        activequiz.loading(null, 'hide');

    });


};

activequiz.jumpto_question = function () {

    if (window.location.hash === '#jumptoquestion-dialog') {
        // if the dialog is open, assume that we want to go to that the question in the select (as the x/close removes the hash and doesn't re-call this function)
        // it is only called on "go to question" button click when dialog is open

        var select = document.getElementById('jtq-selectquestion');
        var qnum = select.options[select.selectedIndex].value;

        this.hide_all_questionboxes();
        this.clear_and_hide_qinfobox();
        this.control_buttons([]);

        var params = {
            'action': 'gotoquestion',
            'qnum': qnum,
            'rtqid': activequiz.get('rtqid'),
            'sessionid': activequiz.get('sessionid'),
            'attemptid': activequiz.get('attemptid'),
            'sesskey': activequiz.get('sesskey')
        };

        activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

            if (status == 500) {
                var loadingbox = document.getElementById('loadingbox');
                loadingbox.classList.add('hidden');

                activequiz.quiz_info('There was an error with your request', true);

                window.alert('there was an error with your request ... ');
                return;
            }

            if (response.lastquestion == 'true') {
                // set a var to signify this is the last question
                activequiz.set('lastquestion', 'true');
            } else {
                activequiz.set('lastquestion', 'false');
            }

            // reset location.hash to nothing so that the modal dialog disappears
            window.location.hash = '';

            // now go to the question
            activequiz.control_buttons(['endquestion', 'toggleresponses', 'togglenotresponded']);
            activequiz.waitfor_question(response.questionid, response.questiontime, response.delay, response.nextstarttime);
        });


    } else { // otherwise open the dialog
        window.location.hash = 'jumptoquestion-dialog';
    }

};

activequiz.show_correct_answer = function () {

    var hide = false;
    if (activequiz.get('showingcorrectanswer') != "undefined") {
        if (activequiz.get('showingcorrectanswer') == 'true') {
            hide = true;
        }
    }

    if (hide) {
        activequiz.quiz_info(null, '');
        // change button text
        var scaBtn = document.getElementById('showcorrectanswer');
        scaBtn.innerHTML = M.util.get_string('show_correct_answer', 'activequiz');
        activequiz.set('showingcorrectanswer', 'false');
        this.reload_results();
    } else {
        activequiz.loading(M.util.get_string('loading', 'activequiz'), 'show');

        var params = {
            'action': 'getrightresponse',
            'rtqid': activequiz.get('rtqid'),
            'sessionid': activequiz.get('sessionid'),
            'attemptid': activequiz.get('attemptid'),
            'sesskey': activequiz.get('sesskey')
        };

        // make sure we end the question (on end_question function call this is re-doing what we just did)
        // but handle_request is also called on ending of the question timer in core.js
        activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

            if (status == 500) {
                var loadingbox = document.getElementById('loadingbox');
                loadingbox.classList.add('hidden');

                activequiz.quiz_info('There was an error with your request', true);

                window.alert('there was an error with your request ... ');
                return;
            }

            activequiz.hide_all_questionboxes();
            activequiz.clear_and_hide_qinfobox();

            activequiz.quiz_info(response.rightanswer, true);

            // change button text
            var scaBtn = document.getElementById('showcorrectanswer');
            scaBtn.innerHTML = M.util.get_string('hide_correct_answer', 'activequiz');

            activequiz.set('showingcorrectanswer', 'true');

            activequiz.control_buttons(['showcorrectanswer']);
            activequiz.loading(null, 'hide');

        });
    }
};

/**
 * Toggles the "show student responses" variable
 */
activequiz.toggle_responses = function () {

    var toggleresponsesBtn = document.getElementById('toggleresponses');

    if (activequiz.get('showstudentresponses') === false) { // if it is false, set it back to true for the student responses to show

        toggleresponsesBtn.innerHTML = M.util.get_string('hidestudentresponses', 'activequiz');

        activequiz.set('showstudentresponses', true);
        activequiz.gather_current_results();
    } else { // if it's set to true, or not set at all, then set it to false when this button is clicked

        toggleresponsesBtn.innerHTML = M.util.get_string('showstudentresponses', 'activequiz');
        activequiz.set('showstudentresponses', false);
        activequiz.clear_and_hide_qinfobox();
    }
};


/**
 * Toggles the "show not responded" variable
 */
activequiz.toggle_notresponded = function () {

    var togglenotrespondedBtn = document.getElementById('togglenotresponded');

    if (activequiz.get('shownotresponded') === false) { // if it is false, set it back to true for the student responses to show

        togglenotrespondedBtn.innerHTML = M.util.get_string('hidenotresponded', 'activequiz');

        activequiz.set('shownotresponded', true);
        activequiz.getnotresponded();
    } else { // if it's set to true, or not set at all, then set it to false when this button is clicked

        togglenotrespondedBtn.innerHTML = M.util.get_string('shownotresponded', 'activequiz');
        activequiz.set('shownotresponded', false);
        activequiz.clear_and_hide_notresponded();
    }
};


activequiz.getnotresponded = function () {

    var params = {
        'action': 'getnotresponded',
        'rtqid': activequiz.get('rtqid'),
        'sessionid': activequiz.get('sessionid'),
        'attemptid': activequiz.get('attemptid'),
        'sesskey': activequiz.get('sesskey')
    };

    activequiz.ajax.create_request('/mod/activequiz/quizdata.php', params, function (status, response) {

        if (status == '500') {
            activequiz.not_responded_info('there was an error getting not responded students', true);
        } else if (status == 200) {
            activequiz.not_responded_info(response.notresponded, true);
        }

    });

};


/**
 * Function to automatically disable/enable buttons from the array passed.
 *
 * @param buttons An array of button ids to have enabled in the in quiz controls buttons
 */
activequiz.control_buttons = function (buttons) {

    var btns = document.getElementById('inquizcontrols').getElementsByClassName('btn');

    // loop through the btns array and find if their id is in the requested buttons
    for (var i = 0; i < btns.length; i++) {
        var elemid = btns[i].getAttribute("id");

        if (buttons.indexOf(elemid) === -1) {
            // it's not in our buttons array
            btns[i].disabled = true;
        } else {
            btns[i].disabled = false;
        }
    }
};


activequiz.not_responded_info = function (notresponded, clear) {

    var notrespondedbox = document.getElementById('notrespondedbox');

    // if clear, make the quizinfobox be empty
    if (clear) {
        notrespondedbox.innerHTML = '';
    }

    if (notresponded == null) {
        notresponded = '';
    }

    if (notresponded == '') {
        return; // return if there is nothing to display
    }

    if (typeof notresponded == 'object') {
        notrespondedbox.appendChild(notresponded);
    } else {
        notrespondedbox.innerHTML = notresponded;
    }

    // if it's hidden remove the hidden class
    if (notrespondedbox.classList.contains('hidden')) {
        notrespondedbox.classList.remove('hidden');
    }
};

activequiz.clear_and_hide_notresponded = function () {

    var notrespondedbox = document.getElementById('notrespondedbox');

    notrespondedbox.innerHTML = '';

    if (!notrespondedbox.classList.contains('hidden')) {
        notrespondedbox.classList.add('hidden');
    }

};