<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <h1><?php echo t("Create a New Password") ?></h1>

    <div class="FormWrapper">
        <div class="instructions"><?php echo t('Reset password and agree to terms') ?></div>
        <?php
        $termsOfServiceUrl = Gdn::config('Garden.TermsOfService', '#');
        $termsOfServiceText = sprintf(t('I agree to the <a id="TermsOfService" class="Popup" target="terms" href="%s">terms of service</a>'), url($termsOfServiceUrl));
        // Make sure to force this form to post to the correct place in case the view is
        // rendered within another view (ie. /dashboard/entry/index/):
        echo $this->Form->open(['action' => url('/entry/confirm'), 'id' => 'Form_User_Confirm']);
        echo $this->Form->errors();
        ?>
        <ul>
            <li>
                <?php
                echo $this->Form->label('Password', 'Password');
                echo wrap(sprintf(t('Your password must be at least %d characters long.'), c('Garden.Password.MinLength')).' '.t('For a stronger password, increase its length or combine upper and lowercase letters, digits, and symbols.'), 'div', ['class' => 'Gloss']);
                echo $this->Form->input('Password', 'password', ['Wrap' => true, 'Strength' => true]);
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Confirm Password', 'PasswordMatch');
                echo $this->Form->input('PasswordMatch', 'password', ['Wrap' => true]);
                echo '<span id="PasswordsDontMatch" class="Incorrect" style="display: none;">'.t("Passwords don't match").'</span>';
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->checkBox('TermsOfService', '@'.$termsOfServiceText, ['value' => '1']);
                ?>
            </li>
            <li class="Buttons">
                <?php echo $this->Form->button('Confirm', ['class' => 'Button Primary']); ?>
            </li>
        </ul>
        <?php echo $this->Form->close(); ?>
    </div>
</div>