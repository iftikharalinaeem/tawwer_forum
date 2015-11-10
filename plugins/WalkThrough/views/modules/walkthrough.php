<script type="text/javascript">

    function startIntro(){
        document.cookie="Vanilla-intro_tourname=<?php echo $this->data('TourName'); ?>";
        var intro = introJs();

        var defaultNextLabel = intro._options['nextLabel'];
        var defaultPreviousLabel = intro._options['prevLabel'];

        intro.setOption('tooltipPosition', 'auto');
        intro.setOption('positionPrecedence', ['left', 'right', 'bottom', 'top'])
        intro.setOption('showStepNumbers', true);
        intro.setOption('steps', <?php echo json_encode($this->data('IntroSteps')); ?>);


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
            document.cookie="Vanilla-intro_currentstep="+intro._currentStep;

            var step = intro._introItems[intro._currentStep];
            redirectIfNeeded(step);
            changeTheLabelForButtons();
        });

        intro.oncomplete(function() {
            document.cookie="Vanilla-intro_completed=1";
        });

        intro.onexit(function() {
            // should put the skipping logic in here
        });

        // Resume on the current step if needed
        if (localStorage.intro_currentStep) {
            intro.goToStep( parseInt(localStorage.intro_currentStep) + 1);
        }
        intro.start();
    }

    startIntro();
</script>



