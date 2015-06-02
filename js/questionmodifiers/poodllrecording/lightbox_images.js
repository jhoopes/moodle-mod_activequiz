// assign top level namespace
var activequiz = activequiz || {};
activequiz.vars = activequiz.vars || {};
activequiz.questionmodifiers = activequiz.questionmodifiers || {};

// create a poodll recording namespace on the question modifiers
activequiz.questionmodifiers.poodllrecording = activequiz.questionmodifiers.poodllrecording || {};

/**
 * Add a light-box effect to the images for results.
 * This helps to create a thumbnail for the instructor to then click and view the image
 *
 * This function is called by javascript inserted into the returning results for poodll recording question type
 * that has a response type of "picture"
 */
activequiz.questionmodifiers.poodllrecording.lightbox_images = function () {

    // get all answer elements (most of which contain the images for the poodll question type
    var answers = document.getElementsByClassName('answer');

    var imgCount = 1;

    for (var x in answers) {
        if (answers.hasOwnProperty(x)) {
            try {
                var childTagType = answers[x].children[0].tagName;

                if (childTagType === "IMG") {
                    // we have a picture answer for a student
                    var parent = answers[x];
                    var element = answers[x].children[0];
                    var imgsrc = element.attributes.src.value;

                    element.classList.add('jslghtbx-thmb');
                    element.setAttribute('data-jslghtbx', '');


                    // create a link element to add to the answer div
                    /*var link = document.createElement("a");
                     link.setAttribute('href', imgsrc);
                     link.setAttribute('data-lightbox', 'image-' + imgCount);
                     link.setAttribute('data-title', 'Drawing ' + imgCount);
                     var linkText = document.createTextNode('Drawing #' + imgCount);
                     link.appendChild(linkText);

                     parent.removeChild(element);
                     parent.appendChild(link);
                     imgCount++;*/
                }

            } catch (exception) {
                // do nothing with an error as it's not an image anyway
            }
        }
    }

    // now lightbox any images
    var options = {
        captions: false,
        responsive: true,
        onopen: function () {
            activequiz.set('delayrefreshresults', 'true');
        },
        onclose: function () {
            activequiz.set('delayrefreshresults', 'false');
        }
    };

    if (typeof activequiz.questionmodifiers.poodllrecording.lightbox == 'undefined') {
        activequiz.questionmodifiers.poodllrecording.lightbox = new Lightbox();
    }

    activequiz.questionmodifiers.poodllrecording.lightbox.load(options);
};
