<?php if (!defined('APPLICATION')) exit();

helpAsset(t('Need More Help?'), anchor("Vanilla API Documentation", 'https://docs.vanillaforums.com/api/'));

echo '<h1>', $this->data('Title'), '</h1>';
$Form = $this->Form; //new gdn_Form();
echo $Form->open();
echo $Form->errors();
?>
<ul>
   <li class="form-group">
      <div class="label-wrap">
         <?php echo $Form->label('Endpoint', 'Endpoint');
         echo '<div class="info">Access your forum\'s API through this Endpoint URL:</div>'; ?>
      </div>
      <div class="input-wrap">
         <?php echo '<pre>https://'.CLIENT_NAME.'/api/v1/</pre>'; ?>
      </div>
   </li>
   <li class="form-group">
      <div class="label-wrap">
         <?php echo $Form->label('Access Token', 'AccessToken');
         echo '<div class="info">This is the access token for api calls. It\'s like a password for the API. <b>Do not give this access token out to anyone. Treat it like a password. Do not use it in any code that is visible to your users (e.g. JavaScript).</b></div>'; ?>
      </div>
      <div class="input-wrap">
         <div class="spoiler">
            <button class="btn btn-primary spoiler-trigger">
               Click here to show Access Token
            </button>
            <div class="spoiler-content">
               <?php echo $Form->textBox('AccessToken'); ?>
            </div>
         </div>
      </div>
   </li>
   <li class="form-group">
      <div class="label-wrap">
         <?php echo $Form->label('User', 'Username');
         echo '<div class="info">This is the name of the user that all API calls will be made as. You can create another user and enter it here. Keep in mind that all calls will be made with this user\'s permissions.</div>'; ?>
      </div>
      <div class="input-wrap">
         <?php echo $Form->textBox('Username'); ?>
      </div>
   </li>
   <li class="form-group">
      <div class="label-wrap">
         <?php echo $Form->label('Security', 'Security');
         echo '<div class="info">'.sprintf('You can make sure that api calls can only be called through ssl. Your ssl url is %s.', 'https://'.CLIENT_NAME).'</div>'; ?>
      </div>
      <div class="input-wrap">
         <?php echo $Form->checkBox('OnlyHttps', 'Only allow API calls through ssl.'); ?>
      </div>
   </li>
</ul>
<?php
echo $Form->close('Save');
?>
