<?php if (!defined('APPLICATION')) exit();
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
?>
<div class="Box GuestBox">
    <h4 class="GuestBox-title">
        <?php echo t('Howdy, Stranger!'); ?>
    </h4>
    <p class="GuestBox-message">
        <?php echo t($this->MessageCode, $this->MessageDefault); ?>
    </p>

    <p class="GuestBox-beforeSignInButton">
        <?php $this->fireEvent('BeforeSignInButton'); ?>
    </p>

    <?php
    if ($this->data('signInUrl')) {
        echo '<div class="P">';
        echo '<div class="SigninButtonWrap">';
        echo anchor(t('Sign In'), $this->data('signInUrl'), 'Button Primary'.(signInPopup() ? ' SignInPopup' : ''), ['rel' => 'nofollow']);
        echo '<p class="SinginInfo">'.t('To view full details, sign in.').'</p>';
        echo '</div>';
        if ($this->data('registerUrl')) {
            echo '<div class="RegisterWrap">';
            echo ' '.anchor(t('Register', t('Apply for Membership', 'Register')), $this->data('registerUrl'), 'Button ApplyButton', ['rel' => 'nofollow']);
            echo '<p class="RegisterInfo">'.t("Don't have an account? Click here to get started!").'</p>';
            echo '</div>';
        }

        echo '</div>';
    }
    ?>
    <?php $this->fireEvent('AfterSignInButton'); ?>
</div>
