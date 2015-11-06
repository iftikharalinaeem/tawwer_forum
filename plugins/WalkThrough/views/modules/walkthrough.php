<script type="text/javascript">

    function startIntro(){
        function onCompleteCallback() {
            var item = intro._introItems[intro._currentStep];
            var currentStep = item.vanilla_step + 1;

            document.cookie="Vanilla-intro_currentstep="+currentStep;
<?php if ($this->data('NextUrl')) : ?>
            window.location.href = '<?php echo $this->data('NextUrl'); ?>';
<?php endif; ?>
        };

        var totalSteps = <?php echo $this->data('TotalSteps'); ?>;
        var intro = introJs();

        intro.setOption('tooltipPosition', 'auto');
        intro.setOption('positionPrecedence', ['left', 'right', 'bottom', 'top'])
        intro.setOption('showStepNumbers', false);
<?php if ($this->data('NextUrl')) : ?>
        intro.setOption('doneLabel', 'Next page')
<?php endif; ?>
        intro.setOption('steps', <?php echo json_encode($this->data('IntroSteps')); ?>);

        intro.oncomplete(onCompleteCallback);
        
        intro.onafterchange(function(target) {
            var item = intro._introItems[intro._currentStep];
            var currentStep = item.vanilla_step;
            if (currentStep === totalSteps) {
                document.cookie="Vanilla-intro_completed=1";
            }
            document.cookie="Vanilla-intro_currentstep="+currentStep;
        });

        intro.goToStep(<?php echo $this->data('IntroStartingStep'); ?>);
        intro.start();
    }

    startIntro();
</script>



