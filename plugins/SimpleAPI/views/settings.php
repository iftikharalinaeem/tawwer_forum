<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->Data('Title'), '</h1>';

$Form = $this->Form; //new Gdn_Form();
echo $Form->Open();
echo $Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $Form->Label('Accesss Token', 'AccessToken');
         echo '<div class="Info2">This is the access token for api calls. It\'s like a password for the API. <b>Do not give this access token out to anyone.</b></div>';
         echo $Form->TextBox('AccessToken', array('class' => 'InputBox BigInput'));
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('User', 'Username');
         echo '<div class="Info2">This is the name of the user that all API calls will be made as. You can create another user and enter it here. Keep in mind that all calls will be made with this user\'s permissions.</div>';
         echo $Form->TextBox('Username');
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Security', 'Security');
         echo '<div class="Info2">'.sprintf('You can make sure that api calls can only be called through ssl. Your ssl url is %s.', 'https://'.CLIENT_NAME).'</div>';
         echo $Form->CheckBox('OnlyHttps', 'Only allow API calls through ssl.');
      ?>
   </li>
</ul>

<?php

echo $Form->Button('Save');
echo $Form->Close();
?>