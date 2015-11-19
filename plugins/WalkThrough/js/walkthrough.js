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
 * The main Garden object
 * @type Object
 */
window.gdn = window.gdn || {};

/**
 * Walkthrough JS functions
 *
 * @param {jQuery} $
 * @param {window} window
 * @param {window.gdn} gdn - The main Garden Object
 */
gdn.WalkThrough = (function ($, window, gdn) {

    // Assigns the localStorage object or create a dummy
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

    // Options coming from Vanilla backend
    var options = {
        _options: gdn.getMeta('Plugin.WalkThrough.Options', []) ,

        get: function(key, defaultValue) {
            if (!this.isset(key)) {
                return defaultValue;
            }
            return this._options[key];
        },

        isset: function(key) {
            return typeof(this._options[key]) !== 'undefined';
        }
    };


    /**
     * The WalkThrough Class
     *
     * It's responsible for displaying and controlling the tour.
     *
     * Uses introJs to display the tour.
     * @link https://github.com/usablica/intro.js
     *
     * @param {Object} options
     * @returns {WalkThroughClass}
     */
    function WalkThroughClass(options) {
        this.options = options;
        this.intro = null;


        /**
         * Get the step index that the tour should start/resume to.
         *
         * Uses both the option 'currentStepIndex' and the localStorage
         * to determined starting index.
         *
         * @returns {Number} - Returns the index of the starting step
         */
        function getStartingStepIndex() {
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
            return currentStepIndex;
        }

        this.startingStepIndex = getStartingStepIndex();
    };

    /**
     * Initialize the tour and it's options.
     *
     * @returns {undefined}
     */
    WalkThroughClass.prototype.initialize = function() {
        var that = this;

        // Mandatory or bail out
        if (! options.isset('steps') || ! options.isset('tourName')) {
            return;
        }

        this.intro = introJs();

        // Sets the default options for introJs.
        // Can be modified from a tour config.
        this.intro.setOption('nextLabel', this.options.get('nextLabel', 'Next &rarr;'));
        this.intro.setOption('nextPageLabel', options.get('nextPageLabel', 'Next page &rarr;'));
        this.intro.setOption('prevLabel', this.options.get('prevLabel', '&larr; Back'));
        this.intro.setOption('prevPageLabel', options.get('prevPageLabel', '&larr; Previous page'));
        this.intro.setOption('skipLabel', options.get('skipLabel', 'Skip'));
        this.intro.setOption('doneLabel', options.get('doneLabel', 'Done'));
        this.intro.setOption('tooltipPosition', options.get('tooltipPosition', 'auto'));
        this.intro.setOption('positionPrecedence', ['bottom', 'top', 'right', 'left']);
        this.intro.setOption('tooltipClass', options.get('tooltipClass', ''));
        this.intro.setOption('highlightClass', options.get('highlightClass', ''));
        this.intro.setOption('exitOnEsc', options.get('exitOnEsc', false));
        this.intro.setOption('exitOnOverlayClick', options.get('exitOnOverlayClick', false));
        this.intro.setOption('showStepNumbers', options.get('showStepNumbers', true));
        this.intro.setOption('keyboardNavigation', options.get('keyboardNavigation', true));
        this.intro.setOption('showButtons', options.get('showButtons', true));
        this.intro.setOption('showBullets', options.get('showBullets', true));
        this.intro.setOption('showProgress', options.get('showProgress', false));
        this.intro.setOption('scrollToElement', options.get('scrollToElement', true));
        this.intro.setOption('overlayOpacity', options.get('overlayOpacity', 0.7));
        this.intro.setOption('disableInteraction', options.get('disableInteraction', true));

        // Keeps a reference of those labels so we can revert back later
        this.defaultNextLabel = this.intro._options.nextLabel;
        this.defaultPreviousLabel = this.intro._options.prevLabel;

        // Sets the steps
        this.intro.setOption('steps', options.get('steps'));

        this.intro.onbeforechange(function(targetElement) {
            that.notifyPlugin('walkthrough/currentstep', {
                TourName: options.get('tourName'),
                CurrentStep: that.intro._currentStep
            });

            var step = that.intro._introItems[that.intro._currentStep];
            that.redirectIfNeeded(step);
        });

        this.intro.onchange(function(targetElement) {
            that.changeTheLabelForButtons();
        });

        this.intro.oncomplete(function() {
            that.notifyPlugin('walkthrough/complete', {
                TourName: options.get('tourName')
            });
        });

        this.intro.onexit(function() {
            that.notifyPlugin('walkthrough/skip', {
                TourName: options.get('tourName')
            });
        });

    };

    /**
     * Notify the WalkThrough Controller.
     *
     * @param {string} path
     * @param {Array} data
     */
    WalkThroughClass.prototype.notifyPlugin = function(path, data) {
        $.ajax({
            type: "POST",
            url: gdn.url(path),
            data: data,
            dataType: 'json'
        });
    };

    /**
     * Start the tour.
     */
    WalkThroughClass.prototype.start = function() {
        if (typeof(gdn) !== 'object') {
            return;
        }

        if (!this.intro) {
            return;
        }

        // Resume on the current step
        this.intro.goToStep(this.startingStepIndex + 1);
        this.intro.start();
    };


    /**
     * Get a step using its index.
     *
     * @param {number} index
     * @returns {Array|null}
     */
    WalkThroughClass.prototype.getStepByIndex = function(index) {
        if (typeof(this.intro._introItems[index]) !== 'undefined') {
            return this.intro._introItems[index];
        }
        return null;
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
    WalkThroughClass.prototype.hrefAreEqual = function(href1, href2) {
        var url1 = window.document.createElement('a');
        url1.href = href1;

        var url2 = window.document.createElement('a');
        url2.href = href2;

        return url1.href === url2.href;
    };

    /**
     * Returns a URL if the step requires a url change.
     *
     * @param {string} step
     * @returns {string}
     */
    WalkThroughClass.prototype.getStepRedirectUrl = function(step) {
        if (typeof(step) === 'undefined') {
            return null;
        }

        if (typeof(step.page) === 'string') {
            if (step.page !== gdn.getMeta('Path')) {
                var newUrl = gdn.url(step.page);

                // Do not return a new url if we are on this page already
                if (this.hrefAreEqual(newUrl, window.location.href)) {
                    return null;
                }

                return newUrl;
            }
        }
        return null;
    };

    /**
     * Redirect to the step URL if needed.
     *
     * @param {Array} step
     */
    WalkThroughClass.prototype.redirectIfNeeded = function(step) {
        // check if the step needs to navigate to another page
        var newUrl = this.getStepRedirectUrl(step);
        if (newUrl) {
            window.location.href = newUrl;

            // Passes the index using localStorage to avoid race condition when switching URL
            localStorage.setItem('intro_startAtIndex', this.intro._currentStep);

            // Prevents the animation to show the next step before the URL changes
            this.intro.disableAnimation(true);
        }
    };

    /**
     * Change the the previous or next button label.
     *
     * @param {string} labelName The name of the label.
     * @param {string} label The text to use as a label.
     * @returns {undefined}
     */
    WalkThroughClass.prototype.changeLabel = function(labelName, label) {
        var buttonClasses = {
            prevLabel: '.introjs-prevbutton',
            nextLabel: '.introjs-nextbutton'
        };

        // Need to change both the option and the button html,
        // since sometimes, the buttons are created at some other time, the buttons are reused
        if (typeof(buttonClasses[labelName]) !== 'undefined') {
            this.intro.setOption(labelName, label);
            $(buttonClasses[labelName]).html(label);
        }
    };

    /**
     * Change the label for the previous and next button.
     *
     * It detects if the steps before or after will change the URL,
     * and if so, will display a different label.
     */
    WalkThroughClass.prototype.changeTheLabelForButtons = function() {
        this.changeLabel('prevLabel', this.defaultPreviousLabel);
        this.changeLabel('nextLabel', this.defaultNextLabel);

        var nextStep = this.getStepByIndex(this.intro._currentStep + 1);
        if (nextStep) {
            var newUrl = this.getStepRedirectUrl(nextStep);
            if (newUrl) {
                this.changeLabel('nextLabel', this.intro._options['nextPageLabel']);
            }
        }

        var previousStep = this.getStepByIndex(this.intro._currentStep - 1);
        if (previousStep) {
            var newUrl = this.getStepRedirectUrl(previousStep);
            if (newUrl) {
                this.changeLabel('prevLabel', this.intro._options['prevPageLabel']);
            }
        }
    };

    var walkThroughInstance = new WalkThroughClass(options);

    // Initialize and starts the tour on document ready.
    $(window.document).ready(function() {
        walkThroughInstance.initialize();
        walkThroughInstance.start();
    });

    /*
     * Exposes a few variables for debugging purposes
     */
    return {
        tourName: options.get('tourName'),
        options: options,
        steps: options.get('steps'),
        startingStepIndex: walkThroughInstance.startingStepIndex,
        getIntroJs: function() {
            return walkThroughInstance.intro;
        }
    };

})(jQuery, window, window.gdn);

