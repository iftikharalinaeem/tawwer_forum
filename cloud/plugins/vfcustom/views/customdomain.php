<?php if (!defined('APPLICATION')) {exit();}

$existingDomain = $this->data('site.domain', false);

$desc = t('Custom domains let you use your own domain name for your forum, like <b>community.yourwebsite.com</b>');
helpAsset(sprintf(t('About %s'), t('Custom Domains')), $desc);

?>
<h1>Custom Domain Name</h1>
<div class="CustomDomain">

    <div class="DNSInformation">
        <div class="DNSRecords">
            <h2>DNS Information</h2>
            <div class="form-group">
                <div class="label-wrap">
                    <span class="label">Vanilla Hostname</span>
                </div>
                <div class="input-wrap">
                    <pre><?php echo $this->data('name'); ?></pre>
                </div>
            </div>
        </div>
    </div>

    <?php if ($existingDomain) { ?>
        <div class="ExistingDomains">
            <div class="form-group alert alert-success">
                <div class="label-wrap-wide">
                    <span class="label">You already have a custom domain: <?php echo wrap($existingDomain, 'span'); ?></span>
                </div>
                <div class="ExistingDomain input-wrap-right">
                    <?php echo anchor("Remove", "settings/customdomain/remove/{$existingDomain}", 'btn btn-primary'); ?>
                </div>
            </div>
        </div>
    <?php } ?>

<?php if ($this->Data('steps')) { ?>
        <ol class="CustomSteps padded">
            <li>If you don't have your own domain already, you can buy one from a registrar like <a href="http://godaddy.com">GoDaddy.com</a> or <a href="http://name.com">Name.com</a>.</li>
            <li>Create a DNS record for your domain so that it points at our servers. The best way is to use a <b>CNAME</b>.</li>
            <li>If you're trying to use a <b>subdomain</b> (like community.yourwebsite.com), create a <b>CNAME</b> record for your domain, pointing at your Vanilla address: <b><?php echo $this->data('name'); ?></b></li>
            <li><b>Wait for your new rule to fully propagate.</b> Use a tool like <a href="https://www.whatsmydns.net/">What's My DNS</a> to check record propagation.</li>
            <li>Once your DNS record has propagated around the internet, enter your chosen custom domain name below and click Continue.</li>
        </ol>
    <?php } ?>

    <?php
    if ($this->Data('attempt', FALSE)) {

        // Failed :(
        if ($this->Data('failed', FALSE)) {
            ?>
            <div class="CustomFailed alert alert-danger padded"><?php echo $this->data('errorText'); ?></div>
        <?php
        }

        // Success!
        if (!$this->Data('failed', FALSE)) {
            ?>

            <?php if ($this->data('removed', false)) { ?>
                <div class="CustomSuccess alert alert-success padded">
                    Your custom domain has been removed. If you find that you have been logged out, simply clear your cookies and log in again.
                </div>
            <?php } else { ?>
                <div class="CustomSuccess alert alert-success padded">
                    Your custom domain has been created and configured! If you find that you have been logged out, simply clear your cookies and log in again.
                </div>
                <?php } ?>
            <?php
            }
        }
        ?>

    <div class="NewDomain">
        <?php echo $this->Form->open(); ?>
        <div class="form-group">
            <div class="label-wrap">
                <span class="label">New Custom Domain</span>
            </div>
            <div class="input-wrap input-wrap-multiple category-url-code">
                <div class="text-control-height">http://</div>
                <?php echo $this->Form->textBox('CustomDomain', ['class' => 'form-control']); ?>
                <?php echo $this->Form->button('Apply'); ?>
            </div>
        </div>
        <?php echo $this->Form->close(); ?>
    </div>

</div>

