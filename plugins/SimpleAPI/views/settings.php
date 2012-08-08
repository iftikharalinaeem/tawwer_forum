<?php if (!defined('APPLICATION')) exit(); ?>
<style class="text/css">
   .ApiEndpoint {
      padding: 10px !important;
      background: #f1f1f1;
      font-size: 16px;
      font-family: "Courier New";
   }
</style>
<div class="Help Aside">
   <?php
   echo Wrap(T('Need More Help?'), 'h2');
   echo '<ul>';
   echo Wrap(Anchor("Vanilla API Documentation", 'http://vanillaforums.com/blog/api/'), 'li');
   echo '</ul>';
   ?>
</div>
<?php
echo '<h1>', $this->Data('Title'), '</h1>';

$Form = $this->Form; //new Gdn_Form();
echo $Form->Open();
echo $Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $Form->Label('Endpoint', 'Endpoint');
         echo '<div class="Info2">Access your forum\'s API through this Endpoint URL:</div>';
         echo '<div class="ApiEndpoint">https://'.CLIENT_NAME.'/api/v1/</blockquote>';
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Access Token', 'AccessToken');
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