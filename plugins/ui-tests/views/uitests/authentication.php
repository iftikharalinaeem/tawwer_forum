<h2>
    <?php include "modules/back.php" ?>
</h2>
<h1>
    Authentication
</h1>

<p>
    <strong>Attention:</strong> This page is intended to test various potential states for React components without needing to create endpoints for them. It's also a good spot check when doing CSS changes that affect many components. These components may or may not fully work on <em>this</em> page. The check is on the hard coded, initial state of the component. Testing the actual component should be on the real page.
</p>

<h3>SSO Methods <a href="<?php echo url('/authenticate/signin'); ?>" target="_blank">/authenticate/signin</a></h3>
<div class="authenticateUserCol">
    <div id="uitest-ssomethods"></div>
</div>

<h3>Simple Password Form <a href="<?php echo url('/authenticate/password'); ?>" target="_blank">/authenticate/password</a></h3>
<div class="authenticateUserCol">

    <h4>Plausible Example</h4>
    <div id="uitest-password-fields"></div>

    <hr/>

    <h4>Extreme example (for testing CSS)</h4>
    <div id="uitest-password-fields-unreasonable"></div>
</div>




<h3>Recover Password <a href="<?php echo url('/authenticate/recoverpassword'); ?>" target="_blank">/authenticate/recoverpassword</a></h3>
<div id="uitest-recoverpassword"></div>
