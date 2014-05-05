<script>
    $( "#ZendeskLogin" ).click(function() {
        window.location.replace('<?php echo $this->Data['LoginUrl']; ?>');
    });

</script>
<h2>
    Zendesk
</h2>
<p>You need to login to your Zendesk Account to continue.</p>
<button id="ZendeskLogin" class="Button BigButton">Login</button>