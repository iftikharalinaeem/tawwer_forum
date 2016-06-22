<script>
   $( "#PegaLogin" ).click(function() {
      window.location.replace('<?php echo $this->Data['LoginURL']; ?>');
   });

</script>
<h2>
   Pega
</h2>
<h3><?php echo $this->Data['LoginURL']; ?></h3>
<p><?php echo T('You need to login to your Pega Account to continue.') ?></p>
<button id="PegaLogin" class="Button BigButton">Login</button>