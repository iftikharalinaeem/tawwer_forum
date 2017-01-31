<script>
    $( document ).ready(function() {
        $( "#ZendeskLogin" ).click(function() {
            window.location.replace('<?php echo $this->Data['LoginUrl']; ?>');
        });
    });
</script>
<h2>
    Zendesk
</h2>
<p><?php echo T('You need to login to your Zendesk Account to continue'); ?>.</p>
<button id="ZendeskLogin" class="Button BigButton"><?php echo T('Login'); ?></button>
