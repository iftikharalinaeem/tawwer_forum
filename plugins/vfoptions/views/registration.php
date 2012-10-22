<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('User Registration Settings'); ?></h1>
<?php
$this->Form->AddHidden('Garden.Registration.CaptchaPublicKey', C('Garden.Registration.CaptchaPublicKey', ''), TRUE);
$this->Form->AddHidden('Garden.Registration.CaptchaPrivateKey', C('Garden.Registration.CaptchaPrivateKey', ''), TRUE);

echo $this->Form->Open();
echo $this->Form->Errors();

?>
<ul>
   <li id="RegistrationMethods">
      <div class="Info"><?php echo T('Change the way that new users register with the site.'); ?></div>
      <table class="Label AltColumns">
         <thead>
            <tr>
               <th><?php echo T('Method'); ?></th>
               <th class="Alt"><?php echo T('Description'); ?></th>
            </tr>
         </thead>
         <tbody>
         <?php
            $Count = count($this->RegistrationMethods);
            $i = 0;
            $Alt = FALSE;
            foreach ($this->RegistrationMethods as $Method => $Description) {
               $Alt = $Alt ? FALSE : TRUE;
               $CssClass = $Alt ? 'Alt' : '';
               ++$i;
               if ($Count == $i)
                  $CssClass .= ' Last';
               
               $CssClass = trim($CssClass);
               ?>
               <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
                  <th><?php
                     $MethodName = $Method;
                     if ($MethodName == 'Captcha')
                        $MethodName = 'Basic';
                        
                     echo $this->Form->Radio('Garden.Registration.Method', $MethodName, array('value' => $Method));
                  ?></th>
                  <td class="Alt"><?php echo T($Description); ?></td>
               </tr>
               <?php
            }
         ?>
         </tbody>
      </table>
   </li>
   <li id="InvitationExpiration">
      <?php
         echo $this->Form->Label('Invitations will expire', 'Garden.Registration.InviteExpiration');
         echo $this->Form->DropDown('Garden.Registration.InviteExpiration', $this->InviteExpirationOptions, array('value' => $this->InviteExpiration));
      ?>
   </li>
   <li id="InvitationSettings">
      <div class="Info"><?php echo T('Choose who can send out invitations to new members:'); ?></div>
      <table class="Label AltColumns">
         <thead>
            <tr>
               <th><?php echo T('Role'); ?></th>
               <th class="Alt"><?php echo T('Invitations per month'); ?></th>
            </tr>
         </thead>
         <tbody>
         <?php
            $i = 0;
            $Count = $this->RoleData->NumRows();
            $Alt = FALSE;
            foreach ($this->RoleData->Result() as $Role) {
               $Alt = $Alt ? FALSE : TRUE;
               $CssClass = $Alt ? 'Alt' : '';
               ++$i;
               if ($Count == $i)
                  $CssClass .= ' Last';
               
               $CssClass = trim($CssClass);
               $RoleID = GetValue('RoleID', $Role);
               $CurrentValue = ArrayValue($RoleID, $this->ExistingRoleInvitations, FALSE);
               ?>
               <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>               
                  <th><?php echo GetValue('Name', $Role); ?></th>
                  <td class="Alt">
                     <?php
                     echo $this->Form->DropDown('InvitationCount[]', $this->InvitationOptions, array('value' => $CurrentValue));
                     echo $this->Form->Hidden('InvitationRoleID[]', array('value' => $RoleID));
                     ?>
                  </td>
               </tr>
               <?php
            }
         ?>
         </tbody>
      </table>
   </li>
   <li>
      <div class="Info">
      <?php
      echo $this->Form->CheckBox('Garden.Registration.ConfirmEmail', '@'.T('Confirm email addresses', 'Require users to confirm their email addresses (recommended)'));

      echo $this->Form->Label('Email Confirmation Role', 'Garden.Registration.ConfirmEmailRole'),
         $this->Form->DropDown('Garden.Registration.ConfirmEmailRole', $this->Data('_Roles'), array('IncludeNull' => TRUE));

      echo ' ', T('Users will be assigned to this role until they\'ve confirmed their email addresses.');
      ?>
      </div>
   </li>
</ul>
<?php echo $this->Form->Close('Save');