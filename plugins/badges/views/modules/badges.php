<?php if (!defined('APPLICATION')) exit();

$Title = ($this->User->UserID == Gdn::session()->UserID) ? t('MyBadgesModuleTitle', 'My Badges') : t('BadgesModuleTitle', 'Badges');
$Title = t($Title);
?>
<div id="Badges" class="Box BadgeGrid<?php if (!count($this->Badges)) echo ' NoItems'; ?>">
    <?php echo panelHeading($Title); ?>
    <div class="PhotoGrid">
    <?php
    if (count($this->Badges) > 20) {
        $CssClass = 'ProfilePhoto ProfilePhotoSmall';
    } else {
        $CssClass = 'ProfilePhoto ProfilePhotoMedium';
    }

    foreach ($this->Badges as $Badge) {
        if (val('Photo', $Badge, false)) {
            echo anchor(
                img(Gdn_Upload::url(changeBasename(val('Photo', $Badge), '%s')), ['class' => $CssClass]),
                url('/badge/'.val('Slug', $Badge), true),
                ['title' => val('Name', $Badge)]
            );
        }
    }

    if (!count($this->Badges)) : ?>
    <span><?php echo t('NoBadgesEarned', 'Any minute now&hellip;'); ?></span>
    <?php endif; ?>
    </div>
</div>
