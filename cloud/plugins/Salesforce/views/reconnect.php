<script>
   $( "#SalesForceLogin" ).click(function() {
      window.location.replace('<?php echo $this->Data['LoginURL']; ?>');
   });

</script>
<h2>
   Salesforce
</h2>
<p><?php echo t('You need to login to your Salesforce Account to continue.') ?></p>
<button id="SalesForceLogin" class="Button BigButton">Login</button>