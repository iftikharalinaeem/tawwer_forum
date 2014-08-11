<script>
    $( "#GithubLogin" ).click(function() {
        window.location.replace('<?php echo $this->Data['LoginURL']; ?>');
    });

</script>
<h2>
    GitHub
</h2>
<p><?php echo T('You need to login to your GitHub account to continue.') ?></p>
<button id="GithubLogin" class="Button BigButton"><?php echo T('Login'); ?></button>