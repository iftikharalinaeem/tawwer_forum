/*
 * Manage the display of a tour
 *
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 * @copyright 2010-2015 Vanilla Forums Inc.
 * @license Proprietary
 * @package internal
 * @subpackage WalkThrough
 * @since 2.0
 */

function walkthroughNotify(path, data) {
    $.ajax({
        type: "POST",
        url: gdn.url(path),
        data: data,
        error: function(XMLHttpRequest, textStatus, errorThrown) {
        },
        success: function(json) {
        },
        dataType: 'json'
    });
};



function startIntro() {
    if (typeof(gdn) !== 'object') {
        return;
    }

    var options = gdn.getMeta('Plugin.WalkThrough.Options', []);
    if (typeof(options.steps) === 'undefined') {
        return;
    }

    if (typeof(options.tourName) === 'undefined') {
        return;
    }

    var intro = introJs();

    var defaultNextLabel = intro._options['nextLabel'];
    var defaultPreviousLabel = intro._options['prevLabel'];

    intro.setOption('tooltipPosition', 'auto');
    intro.setOption('positionPrecedence', ['left', 'right', 'bottom', 'top'])
    intro.setOption('showStepNumbers', true);
    intro.setOption('steps', options.steps);


    function getStepByIndex(index) {
        if (typeof(intro._introItems[index]) !== 'undefined') {
            return intro._introItems[index];
        }
        return null;
    }

    function redirectIfNeeded(step) {
        // check if the step needs to navigate to another page
        var newUrl = getStepUrlIfDifferentThanCurrentUrl(step);
        if (newUrl) {
            window.location.href = newUrl;
        }
    };

    function getStepUrlIfDifferentThanCurrentUrl(step) {
        if (typeof(step.url) === 'string') {
            var anchor = document.createElement('a');
            anchor.href = step.url;
            var newUrl = anchor.href;

            if (newUrl !== window.location.href) {
                return newUrl;
            }
        }
        return null;
    }

    function changeLabel(labelName, label) {
        var buttonClasses = {
            prevLabel: '.introjs-prevbutton',
            nextLabel: '.introjs-nextbutton'
        };

        // Need to change both the option and the button html,
        // since sometimes, the buttons are created and someother time, the buttons are reused
        if (typeof(buttonClasses[labelName]) !== 'undefined') {
            intro.setOption(labelName, label);
            $(buttonClasses[labelName]).html(label);
        }
    }

    function changeTheLabelForButtons() {
        changeLabel('prevLabel', defaultPreviousLabel);
        changeLabel('nextLabel', defaultNextLabel);

        var nextStep = getStepByIndex(intro._currentStep + 1);
        if (nextStep) {
            var newUrl = getStepUrlIfDifferentThanCurrentUrl(nextStep);
            if (newUrl) {
                changeLabel('nextLabel', 'Next page &rarr;');
            }
        }

        var previousStep = getStepByIndex(intro._currentStep - 1);
        if (previousStep) {
            var newUrl = getStepUrlIfDifferentThanCurrentUrl(previousStep);
            if (newUrl) {
                changeLabel('prevLabel', '&larr; Previous page');
            }
        }
    }

    intro.onchange(function(targetElement) {
        localStorage.setItem('intro_currentStep', intro._currentStep);
        walkthroughNotify('walkthrough/currentstep', {
            TourName: options.tourName,
            CurrentStep: intro._currentStep
        });

        var step = intro._introItems[intro._currentStep];
        redirectIfNeeded(step);
        changeTheLabelForButtons();
    });

    intro.oncomplete(function() {
        walkthroughNotify('walkthrough/complete', {
            TourName: options.tourName
        });
    });

    intro.onexit(function() {
        // should put the skipping logic in here
    });

    // Resume on the current step if needed
    if (localStorage.intro_currentStep) {
        intro.goToStep( parseInt(localStorage.intro_currentStep) + 1);
    }

    $(document).ready(function() {
        intro.start();
    });
}

startIntro();
