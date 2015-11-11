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


    var currentStepIndex = 0;
    if (typeof(options.currentStepIndex) !== 'undefined') {
        currentStepIndex = parseInt(options.currentStepIndex);
    }

    if (currentStepIndex < 0) {
        currentStepIndex = 0;
    }

    if (currentStepIndex > options.steps.length - 1) {
        currentStepIndex = options.steps.length - 1;
    }

    var intro = introJs();

    var defaultNextLabel = intro._options['nextLabel'];
    var defaultPreviousLabel = intro._options['prevLabel'];

    intro.setOption('tooltipPosition', 'auto');
    intro.setOption('positionPrecedence', ['left', 'right', 'bottom', 'top'])
    intro.setOption('showStepNumbers', true);
    intro.setOption('exitOnEsc', false);
    intro.setOption('exitOnOverlayClick', false);

    intro.setOption('steps', options.steps);


    function getStepByIndex(index) {
        if (typeof(intro._introItems[index]) !== 'undefined') {
            return intro._introItems[index];
        }
        return null;
    }

    function redirectIfNeeded(step) {
        // check if the step needs to navigate to another page
        var newUrl = getStepUrlIfDifferentThanCurrenPath(step);
        if (newUrl) {
            window.location.href = newUrl;
        }
    };

    /**
     * Returns a URL if the step requires a url change.
     */
    function getStepUrlIfDifferentThanCurrenPath(step) {
        if (typeof(step.page) === 'string') {
            if (step.page !== gdn.getMeta('Path')) {
                return gdn.url(step.page);
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
            var newUrl = getStepUrlIfDifferentThanCurrenPath(nextStep);
            if (newUrl) {
                changeLabel('nextLabel', 'Next page &rarr;');
            }
        }

        var previousStep = getStepByIndex(intro._currentStep - 1);
        if (previousStep) {
            var newUrl = getStepUrlIfDifferentThanCurrenPath(previousStep);
            if (newUrl) {
                changeLabel('prevLabel', '&larr; Previous page');
            }
        }
    }

    intro.onchange(function(targetElement) {
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
        walkthroughNotify('walkthrough/skip', {
            TourName: options.tourName
        });
    });

    // Resume on the current step
    intro.goToStep(currentStepIndex + 1);
    intro.start();
}

$(document).ready(function() {
   startIntro();
});