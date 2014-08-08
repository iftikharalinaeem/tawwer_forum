<script>
    $( "#GithubLogin" ).click(function() {
        window.location.replace('<?php echo $this->Data['LoginURL']; ?>');
    });

</script>
<h2>
    Github
</h2>
<p><?php echo T('You need to login to your Github account to continue.') ?></p>
<button id="GithubLogin" class="Button BigButton">Login</button>