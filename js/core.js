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

// assign top level namespace
var activequiz = activequiz || {};
activequiz.vars = activequiz.vars || {};

/**
 * Adds a variable to the activequiz object
 *
 * @param name the name of the property to set
 * @param value the value of the property to set
 * @returns {activequiz}
 */
activequiz.set = function (name, value) {
    this.vars[name] = value;
    return this;
};

/**
 * Gets a variable from the activequiz object
 *
 * @param name
 * @returns {*}
 */
activequiz.get = function (name) {
    if (typeof this.vars[name] === 'undefined') {
        return 'undefined';
    }

    return this.vars[name];
};

/**
 * Defines ajax functions in its namespace
 *
 *
 * @type {{httpRequest: {}, init: init, create_request: create_request}}
 */
activequiz.ajax = {

    httpRequest: {},

    init: function () {

    },
    /**
     * Create and send a request
     * @param url the path to the file you are calling, note this is only for local requests as siteroot will be added to the front of the url
     * @param params the parameters you'd like to add.  This should be an object like the following example
     *
     *          params = { 'id' : 1, 'questionid': 56, 'answer': 'testing' }
     *
     *                  will convert to these post parameters
     *
     *          'id=1&questionid=56&answer=testing'
     *
     * @param callback callable function to be the callback onreadystatechange, must accept httpstatus and the response
     */
    create_request: function (url, params, callback) {

        // re-init a new request ( so we don't have things running into each other)
        if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            var httpRequest = new XMLHttpRequest();
            if (httpRequest.overrideMimeType) {
                httpRequest.overrideMimeType('text/xml');
            }
        } else if (window.ActiveXObject) { // IE
            try {
                var httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
            }
            catch (e) {
                try {
                    httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
                }
                catch (e) {
                    alert(window.M.utils.get_string('httprequestfail', 'activequiz'));
                }
            }
        }

        httpRequest.onreadystatechange = function () {
            if (this.readyState == 4) {

                var status = this.status;
                var response = '';
                if (status == 500) {
                    try {
                        response = JSON.parse(this.responseText);
                    } catch (Error) {
                        response = '';
                    }
                } else {
                    try {
                        response = JSON.parse(this.responseText);
                    } catch (Error) {
                        response = this.responseText;
                    }

                }
                callback(status, response); // call the callback with the status and response
            }
        };
        httpRequest.open('POST', activequiz.get('siteroot') + url, true);

        var parameters = '';
        if (params instanceof FormData) {
            parameters = params;  // already valid to send with xmlHttpRequest
        } else { // separate it out
            httpRequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            for (var param in params) {
                if (params.hasOwnProperty(param)) {
                    if (parameters.length > 0) {
                        parameters += '&';
                    }
                    parameters += param + '=' + encodeURI(params[param]);
                }
            }
        }

        httpRequest.send(parameters);

        // Resend the request if nothing back from the server within 2 seconds
        //activequiz_delayed_request("activequiz_resend_request()", activequiz.resenddelay);
    }
};


/**
 * Callback for when the quiz page is fully loaded
 * The stop parameter is there just so that people see the loading on fast browsers
 *
 * @param stop whether to actually stop "loading"
 */
activequiz.quiz_page_loaded = function (stop) {

    if (stop) {

        var controls = document.getElementById('controlbox');
        var instructions = document.getElementById('instructionsbox');
        var loadingbox = document.getElementById('loadingbox');

        // initialize ajax object
        activequiz.ajax.init();


        // next insert rtqinitinfo into the activequizvars
        for (var prop in window.rtqinitinfo) {
            if (rtqinitinfo.hasOwnProperty(prop)) {
                this.set(prop, rtqinitinfo[prop]);
            }
        }

        // set the boxes vars in the relatimequiz.vars for access in other functions
        activequiz.set('controlbox', controls);
        activequiz.set('instructionsbox', instructions);
        activequiz.qcounter = false;
        activequiz.set('quizinfobox', document.getElementById('quizinfobox'));

        // see if we're resuming a quiz or not
        if (activequiz.get('resumequiz') == "true") {
            if (controls) {
                controls.classList.remove('hidden');
            }
            this.resume_quiz();
        } else {
            if (controls) {
                controls.classList.remove('hidden');
            }
            instructions.classList.remove('hidden');
            loadingbox.classList.add('hidden');


            // lastly call the instructor/student's quizinfo function
            activequiz.set('inquestion', 'false');
            activequiz.getQuizInfo();
        }

    } else {
        setTimeout(activequiz.quiz_page_loaded(true), 1000);
    }
};

activequiz.resume_quiz = function () {

    var startquizbtn = document.getElementById('startquiz');
    var inquizcontrols = document.getElementById('inquizcontrols');

    switch (this.get('resumequizaction')) {
        case 'waitforquestion':
            // we're waiting for a question so let the quiz info handle that
            // note that there is up to a 3 second offset due to set interval, but, this within an acceptable time offset
            // for the next question to start

            activequiz.getQuizInfo();

            if (inquizcontrols) {
                inquizcontrols.classList.remove('btn-hide');
                startquizbtn.classList.add('btn-hide');
            }

            if (activequiz.get('isinstructor') == 'true') {
                // instructor resume waitfor question needs to be instantiated as their quizinfo doesn't handle the wait for question case
                this.waitfor_question(this.get('resumequizcurrentquestion'), this.get('resumequizquestiontime'), this.get('resumequizdelay'));
            }

            break;
        case 'startquestion':
            if (inquizcontrols) {
                inquizcontrols.classList.remove('btn-hide');
                startquizbtn.classList.add('btn-hide');
                if (this.get('resumequizquestiontime') == 0) {
                    // enable the "end question button"
                    this.control_buttons(['endquestion', 'toggleresponses', 'togglenotresponded']);
                }

            }

            this.goto_question(this.get('resumequizcurrentquestion'), this.get('resumequizquestiontime'), this.get('resumequestiontries'));
            activequiz.set('inquestion', 'true');
            activequiz.getQuizInfo();
            this.loading(null, 'hide');


            break;
        case 'reviewing':

            // setup review for instructors, otherwise display reviewing for students
            if (activequiz.get('isinstructor') == 'true') {
                activequiz.getQuizInfo(); // still start quiz info
                this.loading(null, 'hide');
                // load right controls if available
                if (inquizcontrols) {
                    inquizcontrols.classList.remove('btn-hide');
                    startquizbtn.classList.add('btn-hide');
                }
                activequiz.set('inquestion', 'false');
                activequiz.set('currentquestion', this.get('resumequizcurrentquestion'));
                activequiz.set('endquestion', 'true');
                this.reload_results();
            } else {
                activequiz.getQuizInfo(); // still start quiz info
                this.loading(null, 'hide');
                this.quiz_info(M.util.get_string('waitforrevewingend', 'activequiz'), true);
            }


            break;
    }

};


/**
 * General function for waiting for the question
 *
 * @param questionid
 * @param questiontime
 * @param delay
 */
activequiz.waitfor_question = function (questionid, questiontime, delay) {


    var quizinfobox = activequiz.get('quizinfobox');

    var quizinfotext = document.createElement('div');
    quizinfotext.innerHTML = M.util.get_string('waitforquestion', 'activequiz');
    quizinfotext.setAttribute('id', 'quizinfotext');
    quizinfotext.setAttribute('style', 'display: inline-block');

    var quizinfotime = document.createElement('div');

    // set the timeLeft and then set interval to count down
    quizinfotime.innerHTML = "&nbsp;" + delay.toString() + " " + M.util.get_string('seconds', 'moodle');
    activequiz.set('timeLeft', delay);

    activequiz.counter = setInterval(function () {
        var timeLeft = activequiz.get('timeLeft');
        timeLeft--;
        activequiz.set('timeLeft', timeLeft);
        if (timeLeft <= 0) {
            clearInterval(activequiz.counter);
            activequiz.goto_question(questionid, questiontime);
        } else {
            quizinfotime.innerHTML = "&nbsp;" + timeLeft.toString() + " " + M.util.get_string('seconds', 'moodle');
        }
    }, 1000);

    quizinfotime.setAttribute('id', 'quizinfotime');
    quizinfotime.setAttribute('style', 'display: inline-block;');
    quizinfobox.innerHTML = '';
    quizinfobox.appendChild(quizinfotext);
    quizinfobox.appendChild(quizinfotime);

    quizinfobox.classList.remove('hidden');

    var instructionsbox = activequiz.get('instructionsbox');
    instructionsbox.classList.add('hidden');
};


activequiz.goto_question = function (questionid, questiontime, tries) {

    this.clear_and_hide_qinfobox();

    var questionbox = document.getElementById('q' + questionid + '_container');
    questionbox.classList.remove('hidden');

    var settryno = false;

    // make sure the trycount is always correct (this is for re-polling of questions for students, and for resuming of a quiz.
    if (activequiz.get('isinstructor') == 'false') {

        var questions = activequiz.get('questions');
        var question = questions[questionid];
        var tottries = question.tries;

        if(tries != null) {
            if( tries > 0 && tottries > 1) {

                var tryno = (tottries - tries) + 1;
                activequiz.set('tryno', tryno);
                settryno = true; // setting to true so we don't overwrite later as the try number being 1

                this.update_tries(tries, questionid);
            }else if (tries > 0 && tottries == 1) {
                // let the question proceed for their first try on a 1 try question
            }else {
                this.hide_all_questionboxes();
                this.quiz_info(M.util.get_string('notries', 'activequiz'));
                activequiz.set('currentquestion', questionid);
                return; // return early so that we don't start any questions when there are no tries left.
            }
        }else { // there's no resuming tries to set to, so just set to the total tries, if it's greater than 1.
            if(tottries > 1) {
                this.update_tries(tottries, questionid);
            }
        }

    }

    // check to see if questiontime is 0.  If it is 0, then we want to have no timer for this question
    // this is so we don't need a ton of fields passed to this function, as question time of 0 is sufficient
    // for no timer.
    // Also make sure the questiontimetext is there if we have a timer for this question
    var questiontimer = document.getElementById('q' + questionid + '_questiontime');
    var questiontimertext = document.getElementById('q' + questionid + '_questiontimetext');
    if (questiontime == 0) {

        questiontimer.innerHTML = "&nbsp;";
        questiontimertext.innerHTML = "&nbsp;";
        activequiz.qcounter = false; // make sure this is false for the if statements in other functions that clear the timer if it's there
        // QuizInfo will handle the end of a question for students
        // for instructors they are the initiators of a question end so they won't need an update

    } else { // otherwise set up the timer
        questiontimertext.innerHTML = M.util.get_string('timertext', 'activequiz');
        questiontimer.innerHTML = "&nbsp;" + questiontime + ' ' + M.util.get_string('seconds', 'moodle');


        var questionend = new Date();
        questiontime = questiontime * 1000; // convert to miliseconds to add to Date.getTime()
        var questionendtime = questionend.getTime() + questiontime;

        activequiz.set('questionendtime', questionendtime);
        //activequiz.set('timeLeft', questiontime);

        activequiz.qcounter = setInterval(function () {
            /*var timeLeft = activequiz.get('timeLeft');
             timeLeft--;
             activequiz.set('timeLeft', timeLeft);*/

            var currenttime = new Date();
            var currenttimetime = currenttime.getTime();

            if (currenttimetime > activequiz.get('questionendtime')) {
                activequiz.set('inquestion', 'false'); // no longer in question
                clearInterval(activequiz.qcounter);
                activequiz.qcounter = false;
                activequiz.handle_question(questionid);
            } else {

                // get timeLeft in seconds
                var timeLeft = (activequiz.get('questionendtime') - currenttimetime) / 1000;
                timeLeft = number_format(timeLeft, 0, '.', ',');

                questiontimer.innerHTML = "&nbsp;" + timeLeft.toString() + " " + M.util.get_string('seconds', 'moodle');
            }
        }, 1000);
    }

    if(settryno == false) {
        activequiz.set('tryno', 1);
    }
    activequiz.set('currentquestion', questionid);
};

/**
 * Wrapper for handle_question when the user clicks save
 *
 */
activequiz.save_question = function () {

    var currentquestion = activequiz.get('currentquestion');

    // current question refers to the slot number
    // check if the question has more than 1 try, if so don't clear the timer, and just handle question
    var questions = activequiz.get('questions');
    var question = questions[currentquestion];
    var tottries = question.tries;
    if (tottries > 1) {
        // if there are already tries get the current try number
        var tryno;
        if (activequiz.get('tryno') !== 'undefined') {
            tryno = activequiz.get('tryno');
            // set the new tryno as the next one
            tryno++;
            activequiz.set('tryno', tryno);
            this.update_tries((tottries - tryno) + 1, currentquestion);
        } else {
            // set the try number as 2
            activequiz.set('tryno', 2);
            tryno = 2;
        }

        // if the try number is less than the total tries then just handle qestion, don't hide or clear anything
        if (tryno <= tottries) {
            this.handle_question(currentquestion, false);
            return;
        }
    }

    // this code is run if there are nor more tries, or if the total number of tries is 1

    // clear the activequiz counter interval
    if (activequiz.qcounter) {
        clearInterval(activequiz.qcounter);
    }

    var questiontimertext = document.getElementById('q' + currentquestion + '_questiontimetext');
    var questiontimer = document.getElementById('q' + currentquestion + '_questiontime');

    questiontimertext.innerHTML = '';
    questiontimer.innerHTML = '';

    this.handle_question(currentquestion);
};


/**
 * Util function to hide all question boxes
 *
 */
activequiz.hide_all_questionboxes = function () {

    if (activequiz.get('questions') != 'undefined') {
        var allquestions = activequiz.get('questions');
        for (var prop in allquestions) {
            if (allquestions.hasOwnProperty(prop)) {
                var qnum = allquestions[prop].slot;
                var qcont = document.getElementById('q' + qnum + '_container');
                // only do this for elements actually found
                if (typeof qcont != 'undefined') {
                    if (qcont.classList.contains('hidden')) {
                        // already hidden
                    } else {
                        qcont.classList.add('hidden');
                    }
                }
            }
        }
    }
};

/**
 * Util function to clear and hide the quizinfobox
 *
 */
activequiz.clear_and_hide_qinfobox = function () {

    var quizinfobox = document.getElementById('quizinfobox');

    if (!quizinfobox.classList.contains('hidden')) {
        quizinfobox.classList.add('hidden');
    }

    var notrespondedbox = document.getElementById('notrespondedbox');

    if (notrespondedbox) {
        if (!notrespondedbox.classList.contains('hidden')) {
            notrespondedbox.classList.add('hidden');
        }
    }

    quizinfobox.innerHTML = '';
};

/**
 * Utility function to show/hide the loading box
 * As well as provide a string to place in the loading text
 *
 * @param string
 * @param action
 */
activequiz.loading = function (string, action) {

    var loadingbox = document.getElementById('loadingbox');
    var loadingtext = document.getElementById('loadingtext');

    if (action === 'hide') {

        // hides the loading box
        if (!loadingbox.classList.contains('hidden')) {
            loadingbox.classList.add('hidden');
        }
    } else if (action === 'show') {
        // show the loading box with the string provided

        if (loadingbox.classList.contains('hidden')) {
            loadingbox.classList.remove('hidden');
        }
        loadingtext.innerHTML = string;
    }
};

/**
 * Utility class to display information in the quizinfobox
 *
 * @param quizinfo
 * @param clear  bool for whether or not to clear the quizinfobox
 */
activequiz.quiz_info = function (quizinfo, clear) {

    var quizinfobox = document.getElementById('quizinfobox');

    // if clear, make the quizinfobox be empty
    if (clear) {
        quizinfobox.innerHTML = '';
    }


    if (quizinfo == null) {
        quizinfo = '';
    }

    if (quizinfo == '') {
        return; // display nothing if there is nothing
    }

    if (typeof quizinfo == 'object') {
        quizinfobox.appendChild(quizinfo);
    } else {
        quizinfobox.innerHTML = quizinfo;
    }

    // if it's hidden remove the hidden class
    if (quizinfobox.classList.contains('hidden')) {
        quizinfobox.classList.remove('hidden');
    }
};

/**
 * Update the trycount string for the correct count number
 *
 * @param count The number of tries left
 * @param qnum the question number to update
 */
activequiz.update_tries = function (count, qnum) {

    var trybox = document.getElementById('q' + qnum + '_trycount');
    var a = {
        'tries': count
    };
    // update the trycount string
    trybox.innerHTML = M.util.get_string('trycount', 'activequiz', a);

};


// Utility functions

/**
 * PHP JS function for number_format analog
 *
 *
 * @param number
 * @param decimals
 * @param dec_point
 * @param thousands_sep
 * @returns {*|string}
 */
function number_format(number, decimals, dec_point, thousands_sep) {
    //  discuss at: http://phpjs.org/functions/number_format/
    number = (number + '')
        .replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + (Math.round(n * k) / k)
                    .toFixed(prec);
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
        .split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '')
            .length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1)
            .join('0');
    }
    return s.join(dec);
}