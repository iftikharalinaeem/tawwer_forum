<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<div class="alert alert-info padded">
<?php echo t('Need More Help?').' '.sprintf(
                t('Read our docs on %s'),
                anchor(t('Vanilla Pop'), 'http://docs.vanillaforums.com/addons/vanilla-pop/')
            ); ?>
</div>
<?php
$IncomingAddress = $this->data('IncomingAddress');
//$OutgoingAddress = c();
if ($IncomingAddress):
    ?>
    <div class="padded">
        <p>Your forum's email address is <code><?php echo $IncomingAddress ?></code>.
            If you want to set up your own email address for the site then forward it to this one.
            We recommend using the same account as your outgoing address so that people can reply to email sent by the
            application.</p>

        <p>
            <b>New!</b> You can also set up additional email addresses to forward to individual categories.
            To do this forward email to <code><?php echo $this->data('CategoryAddress'); ?></code>.
        </p>
    </div>
<?php endif; ?>

<?php
$Cf = $this->ConfigurationModule;

$Cf->render();
