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

    var localStorage;
    if (typeof(window.localStorage) === 'undefined') {
        // provide mock localStorage object for old browsers
        localStorage = {
            setItem: function(key, value) {},
            getItem: function(key) { return null; },
            removeItem: function(key) { return null; }
        };
    } else {
        localStorage = window.localStorage;
    }

    /**
     * Notify the WalkThrough Controller.
     *
     * @param {string} path
     * @param {Array} data
     */
    function walkthroughNotify(path, data) {
        $.ajax({
            type: "POST",
            url: gdn.url(path),
            data: data,
            dataType: 'json'
        });
    };

    /**
     * Check if 2 hrefs are equals.
     *
     * Compares both absolute and relative urls.
     *
     * @param {string} href1
     * @param {string} href2
     * @returns {Boolean}
     */
    function hrefAreEqual(href1, href2) {
        var url1 = window.document.createElement('a');
        url1.href = href1;

        var url2 = window.document.createElement('a');
        url2.href = href2;

        return url1.href === url2.href;
    }

    /**
     * Start the tour.
     */
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

        // Uses the localStorage to avoid race condition when switching URL between steps
        var startIndexFromStorage = parseInt(localStorage.getItem('intro_startAtIndex'));
        if (startIndexFromStorage >= 0) {
            currentStepIndex = startIndexFromStorage;
            localStorage.removeItem('intro_startAtIndex');
        }

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

        /**
         * Get a step using its index.
         *
         * @param {number} index
         * @returns {Array|null}
         */
        function getStepByIndex(index) {
            if (typeof(intro._introItems[index]) !== 'undefined') {
                return intro._introItems[index];
            }
            return null;
        }

        /**
         * Redirect to the step URL if needed.
         *
         * @param {Array} step
         */
        function redirectIfNeeded(step) {
            // check if the step needs to navigate to another page
            var newUrl = getStepRedirectUrl(step);
            if (newUrl) {
                window.location.href = newUrl;

                // Passes the index using localStorage to avoid race condition when switching URL
                localStorage.setItem('intro_startAtIndex', intro._currentStep);

                // Prevents the animation to show the next step before the URL changes
                intro.disableAnimation(true);
            }
        };

        /**
         * Returns a URL if the step requires a url change.
         *
         * @param {string} step
         * @returns {string}
         */
        function getStepRedirectUrl(step) {
            if (typeof(step) === 'undefined') {
                return null;
            }

            if (typeof(step.page) === 'string') {
                if (step.page !== gdn.getMeta('Path')) {
                    var newUrl = gdn.url(step.page);

                    // Do not return a new url if we are on this page already
                    if (hrefAreEqual(newUrl, window.location.href)) {
                        return null;
                    }

                    return newUrl;
                }
            }
            return null;
        }

        /**
         * Change the the previous or next button label.
         *
         * @param {string} labelName The name of the label.
         * @param {string} label The text to use as a label.
         * @returns {undefined}
         */
        function changeLabel(labelName, label) {
            var buttonClasses = {
                prevLabel: '.introjs-prevbutton',
                nextLabel: '.introjs-nextbutton'
            };

            // Need to change both the option and the button html,
            // since sometimes, the buttons are created at some other time, the buttons are reused
            if (typeof(buttonClasses[labelName]) !== 'undefined') {
                intro.setOption(labelName, label);
                $(buttonClasses[labelName]).html(label);
            }
        }

        /**
         * Change the label for the previous and next button.
         *
         * It detects if the steps before or after will change the URL,
         * and if so, will display a different label.
         */
        function changeTheLabelForButtons() {
            changeLabel('prevLabel', defaultPreviousLabel);
            changeLabel('nextLabel', defaultNextLabel);

            var nextStep = getStepByIndex(intro._currentStep + 1);
            if (nextStep) {
                var newUrl = getStepRedirectUrl(nextStep);
                if (newUrl) {
                    changeLabel('nextLabel', intro._options['nextPageLabel']);
                }
            }

            var previousStep = getStepByIndex(intro._currentStep - 1);
            if (previousStep) {
                var newUrl = getStepRedirectUrl(previousStep);
                if (newUrl) {
                    changeLabel('prevLabel', intro._options['prevPageLabel']);
                }
            }
        }

        intro.onbeforechange(function(targetElement) {
            walkthroughNotify('walkthrough/currentstep', {
                TourName: options.get('tourName'),
                CurrentStep: intro._currentStep
            });

            var step = intro._introItems[intro._currentStep];
            redirectIfNeeded(step);
        });

        intro.onchange(function(targetElement) {
            changeTheLabelForButtons();
        });

        intro.oncomplete(function() {
            walkthroughNotify('walkthrough/complete', {
                TourName: options.get('tourName')
            });
        });

        intro.onexit(function() {
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
