<?php

if (!defined('APPLICATION')) {
    exit();
}

?>
<script type="text/javascript">

var selfdeploy = {

    lock: null,
    timer: null,
    frequency: 10,

    /**
     * Start up selfdeploy live
     *
     * @param {Object} state
     * @returns {undefined}
     */
    init: function(state) {
        if (state.hasOwnProperty('frequency')) {
            selfdeploy.frequency = state.frequency;
        }
        selfdeploy.apply({
            lock: state.lock,
            status: state.status
        });

        // Start checking
        selfdeploy.check();

        jQuery('.self-deploy .do-deploy .deployer').on('click', function(e){
            selfdeploy.deploy();
            return false;
        });
    },

    /**
     * Check current deploy state
     *
     * @returns {undefined}
     */
    check: function() {
        // Stop timers
        clearTimeout(selfdeploy.timer);

        jQuery.ajax(gdn.url('/settings/deploy/status'), {
            type: 'get',
            dataType: 'json',
            success: function(data) {
                // Allow frequency updates
                if (data.hasOwnProperty('Frequency')) {
                    selfdeploy.frequency = data.Frequency;
                }

                selfdeploy.apply({
                    lock: data.DeployLock,
                    status: data.DeployStatus
                });
            },
            complete: function() {
                // Schedule next check
                //console.log('timing next check for '+selfdeploy.frequency+' seconds');
                selfdeploy.timer = setTimeout(function(){
                    selfdeploy.check();
                }, selfdeploy.frequency * 1000);
            }
        });
    },

    /**
     * Apply latest
     *
     * @param {Object} state
     * @returns {undefined}
     */
    apply: function(state, progress) {
        progress = (typeof progress === "undefined") ? true : progress;

        jQuery('.self-deploy .js-lock-panel').hide();

        var panel = jQuery('.self-deploy .lock-'+state.lock).first();
        panel.show();
        selfdeploy.lock = state.lock;

        // Set text status
        if (state.hasOwnProperty('status') && panel.find('.progress .text-status').length > 0) {
            panel.find('.progress .text-status').html(state.status);
        }

        // Checker bar
        if (progress && panel.find('.progress-track').length > 0) {
            panel.find('.progress-track .progress-bar').stop().css('width','0%').animate({
                width: "100%"
            }, selfdeploy.frequency * 1000, 'linear');
        }
    },

    /**
     * Deploy code
     *
     */
    deploy: function() {

        // Check in 3 seconds
        var waitFreq = 3;
        //console.log('timing next check for '+waitFreq+' seconds');
        clearTimeout(selfdeploy.timer);
        selfdeploy.timer = setTimeout(function(){
            selfdeploy.check();
        },waitFreq * 1000);

        // Start optimistically
        selfdeploy.apply({
            lock: "site",
            status: "verifying deploy permission"
        }, false);

        // Send call
        jQuery.ajax(gdn.url('/settings/deploy/send'), {
            type: 'post',
            dataType: 'json'
        });
    }

};

jQuery(document).ready(function($) {
    selfdeploy.init({
        lock: "<?php echo $this->data("DeployLock"); ?>",
        status: "<?php echo $this->data("DeployStatus"); ?>"
    });
});

</script>
<?php echo heading(t('VIP Code Deployment')); ?>
<div class="self-deploy">
    <div class="info"><p>
        Your site's customizations have been configured for self deployment. You may use this
        tool to queue a "pull" of your organization's code repository, followed by deployment
        of that code to the cluster.
    </p></div>

    <div class="deploy-info">

        <h2>Repository</h2>
        <div class="repository">
            <div><?php echo $this->data('Deploy.Repo'); ?></div>
        </div>

    </div>

    <div class="deploy">
        <?php echo $this->Form->open(); ?>
        <h2>Deploy</h2>

        <!-- Other customer is currently deploying -->
        <div class="js-lock-panel lock-cluster deploying cluster-deploying">
            <div>
                A code deployment is currently executing for this cluster! Only one deployment may execute at a time, please be patient
                while this process completes.
            </div>
            <div class="progress-track">
                <div class="progress-bar"></div>
            </div>
        </div>

        <!-- We are currently deploying -->
        <div class="js-lock-panel lock-site deploying self-deploying">
            <div>
                Code deployment is currently executing for this site.
            </div>
            <div class="progress">
                <span>Progress:</span>
                <span class="text-status"></span>
            </div>
            <div class="progress-track">
                <div class="progress-bar"></div>
            </div>
        </div>

        <!-- Allow a deploy -->
        <div class="js-lock-panel lock-free do-deploy">
            <div class="caution">
                Depending on your cluster's server count, deploys can take anywhere from <em>30 seconds to several minutes</em> to complete.
                <b>Please, be patient</b>.
            </div>

            <?php echo $this->Form->button('Deploy', [
                'class' => 'Button deployer'
            ]); ?>
        </div>
        <?php echo $this->Form->close(); ?>
    </div>

</div>

