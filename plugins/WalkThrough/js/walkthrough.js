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

/**
 * Walkthrough JS functions
 *
 * @param {jQuery} $
 * @param {window} window
 * @param {window.gdn} gdn - The main Garden Object
 */
(function ($, window, gdn) {

    function walkthroughNotify(path, data) {
        $.ajax({
            type: "POST",
            url: gdn.url(path),
            data: data,
            dataType: 'json'
        });
    };


    function startIntro() {
        if (typeof(gdn) !== 'object') {
            return;
        }

        // Options coming from Vanilla backend
        var options = function(gdn) {
            var _options = gdn.getMeta('Plugin.WalkThrough.Options', []);

            function _isset(key) {
                    return typeof(_options[key]) !== 'undefined';
            };

            return {
                get: function (key, defaultValue) {
                    if (!_isset(key)) {
                        return defaultValue;
                    }
                    return _options[key];
                },

                isset: _isset
            };
        }(gdn);

        // Mandatory or bail out
        if (! options.isset('steps') || ! options.isset('tourName')) {
            return;
        }

        var currentStepIndex = parseInt(options.get('currentStepIndex', 0));
        var steps = options.get('steps');

        if (currentStepIndex < 0) {
            currentStepIndex = 0;
        }

        if (currentStepIndex > steps.length - 1) {
            currentStepIndex = steps.length - 1;
        }

        var intro = introJs();


        // Sets the default options for introJs.
        // Can be modified from a tour config.
        intro.setOption('nextLabel', options.get('nextLabel', 'Next &rarr;'));
        intro.setOption('nextPageLabel', options.get('nextPageLabel', 'Next page &rarr;'));
        intro.setOption('prevLabel', options.get('prevLabel', '&larr; Back'));
        intro.setOption('prevPageLabel', options.get('prevPageLabel', '&larr; Previous page'));
        intro.setOption('skipLabel', options.get('skipLabel', 'Skip'));
        intro.setOption('doneLabel', options.get('doneLabel', 'Done'));
        intro.setOption('tooltipPosition', options.get('tooltipPosition', 'auto'));
        intro.setOption('positionPrecedence', ['bottom', 'top', 'right', 'left']);
        intro.setOption('tooltipClass', options.get('tooltipClass', ''));
        intro.setOption('highlightClass', options.get('highlightClass', ''));
        intro.setOption('exitOnEsc', options.get('exitOnEsc', false));
        intro.setOption('exitOnOverlayClick', options.get('exitOnOverlayClick', false));
        intro.setOption('showStepNumbers', options.get('showStepNumbers', true));
        intro.setOption('keyboardNavigation', options.get('keyboardNavigation', true));
        intro.setOption('showButtons', options.get('showButtons', true));
        intro.setOption('showBullets', options.get('showBullets', true));
        intro.setOption('showProgress', options.get('showProgress', false));
        intro.setOption('scrollToElement', options.get('scrollToElement', true));
        intro.setOption('overlayOpacity', options.get('overlayOpacity', 0.7));
        intro.setOption('disableInteraction', options.get('disableInteraction', true));

        // Sets the steps
        intro.setOption('steps', steps);

        var defaultNextLabel = intro._options['nextLabel'];
        var defaultPreviousLabel = intro._options['prevLabel'];

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
         *
         * @param {string} step
         * @returns {string}
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
                    changeLabel('nextLabel', intro._options['nextPageLabel']);
                }
            }

            var previousStep = getStepByIndex(intro._currentStep - 1);
            if (previousStep) {
                var newUrl = getStepUrlIfDifferentThanCurrenPath(previousStep);
                if (newUrl) {
                    changeLabel('prevLabel', intro._options['prevPageLabel']);
                }
            }
        }

        intro.onchange(function(targetElement) {
            walkthroughNotify('walkthrough/currentstep', {
                TourName: options.get('tourName'),
                CurrentStep: intro._currentStep
            });

            var step = intro._introItems[intro._currentStep];
            redirectIfNeeded(step);
            changeTheLabelForButtons();
        });

        intro.oncomplete(function() {
            walkthroughNotify('walkthrough/complete', {
                TourName: options.get('tourName')
            });
        });

        intro.onexit(function() {
            // should put the skipping logic in here
            walkthroughNotify('walkthrough/skip', {
                TourName: options.get('tourName')
            });
        });

        // Resume on the current step
        intro.goToStep(currentStepIndex + 1);
        intro.start();
    }

    $(window.document).ready(function() {
       startIntro();
    });

})(jQuery, window, window.gdn);
