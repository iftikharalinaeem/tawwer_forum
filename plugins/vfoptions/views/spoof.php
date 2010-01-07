<?php if (!defined('APPLICATION')) exit(); ?>
<h1>Admin Spoof</h1>
<div class="Legal">
   <p>You can spoof the admin user on this domain by entering the credentials of an administrative user at VanillaForums.com below.</p>
   <p>Note: you can spoof ANY UserID on this forum by entering the UserID in the url after /spoof/ as well.</p>
</div>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
      echo $this->Form->Label('VF.com Admin Email', 'Email');
      echo $this->Form->TextBox('CustomDomain');
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('VF.com Admin Password', 'Password');
      echo $this->Form->Input('Password', 'password');
      ?>
   </li>
</ul>
<?php
echo $this->Form->Close('Spoof →');