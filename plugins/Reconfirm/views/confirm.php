<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <h1><?php echo t("Create a New Password") ?></h1>

    <div class="FormWrapper">
        <div class="instructions"><?php echo t('ReconfirmTermsInstruction') ?></div>
        <?php
        $TermsOfServiceUrl = Gdn::config('Garden.TermsOfService', '#');
        $TermsOfServiceText = sprintf(t('I agree to the <a id="TermsOfService" class="Popup" target="terms" href="%s">terms of service</a>'), url($TermsOfServiceUrl));
        // Make sure to force this form to post to the correct place in case the view is
        // rendered within another view (ie. /dashboard/entry/index/):
        echo $this->Form->open(array('Action' => url('/entry/confirm'), 'id' => 'Form_User_Confirm'));
        echo $this->Form->errors();
        ?>
        <ul>
            <li>
                <?php
                echo $this->Form->label('Password', 'Password');
                echo wrap(sprintf(t('Your password must be at least %d characters long.'), c('Garden.Registration.MinPasswordLength')), 'div', array('class' => 'Gloss'));
                echo $this->Form->Input('Password', 'password', array('Wrap' => true, 'Strength' => TRUE));
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Confirm Password', 'PasswordMatch');
                echo $this->Form->Input('PasswordMatch', 'password', array('Wrap' => TRUE));
                echo '<span id="PasswordsDontMatch" class="Incorrect" style="display: none;">'.t("Passwords don't match").'</span>';
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->CheckBox('TermsOfService', '@'.$TermsOfServiceText, array('value' => '1'));
                echo $this->Form->CheckBox('RememberMe', 'Remember me on this computer', array('value' => '1'));
                ?>
            </li>
            <li class="Buttons">
                <?php echo $this->Form->button('Confirm', array('class' => 'Button Primary')); ?>
            </li>
        </ul>
        <?php echo $this->Form->close(); ?>
    </div>
</div>
