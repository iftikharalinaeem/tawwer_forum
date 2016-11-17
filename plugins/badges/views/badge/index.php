<?php if (!defined('APPLICATION')) exit(); ?>
<style>
    .ProfilePhotoMedium {
        height: 24px;
        margin-bottom: 3px;
        margin-right: 3px;
        vertical-align: middle;
        width: 24px;
    }

    .RecentRecipients {
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    .RecentRecipients .CellWrap {
        display: inline-block;
        margin: 0;
        padding: 0;
        width: 33%;
        box-sizing: border-box;
        overflow: hidden;
        float: left;
    }

    .RecentRecipients .Cell {
        padding: 3px;
    }
</style>

<div class="Badge-Details">
<?php
$Session = Gdn::session();

$this->title(t('View Badge') . ': ' . $this->data('Badge.Name')); ?>

<?php if ($Photo = $this->data('Badge.Photo')) {
    echo img(
        Gdn_Upload::url(changeBasename($Photo, '%s')),
        ['alt' => $this->data('Badge.Name'), 'class' => 'BadgePhotoDisplay']
    );
} ?>

<h1><?php echo Gdn_Format::text($this->data('Badge.Name')); ?></h1>
<p><?php echo Gdn_Format::html($this->data('Badge.Body')); ?></p>
</div>

<div class="Badge-Earned">
<?php if ($this->data('UserBadge.DateCompleted')) : ?>
<div class="EarnedThisBadge">
    <?php echo UserPhoto(Gdn::session()->User); echo t('YouEarnedBadge', 'You earned this badge'); ?>
    <span class="DateReceived"><?php echo Gdn_Format::date($this->data('UserBadge.DateCompleted'), 'html'); ?></span>
</div>
<?php endif; ?>

<?php if ((count($this->data('Recipients')) > 0) && ($Count = $this->data('Badge.CountRecipients'))) : ?>

    <p class="BadgeCountDisplay"><?php
        $text = plural($Count, t('%s person has earned this badge.'), t('%s people have earned this badge.'));
        echo (checkPermission('Reputation.Badges.Give')) ? anchor($text, '/badge/recipients/'.$this->data('Badge.BadgeID')) : $text;
    ?></p>

    <h2><?php echo t('BadgeRecipientsHeading', "Most recent recipients"); ?></h2>
    <div class="RecentRecipients">
        <?php foreach($this->Data('Recipients', []) as $User) : ?>
        <div class="CellWrap"><div class="Cell"><?php
            echo userPhoto($User);
            echo userAnchor($User);
        ?> <span class="DateReceived"><?php echo Gdn_Format::date(val('DateCompleted', $User), 'html'); ?> </span>
            </div></div>
        <?php endforeach; ?>
    </div>

<?php else : ?>

    <p><?php echo t('BadgesNobody', 'Nobody has earned this badge yet.'); ?></p>

<?php endif; ?>
</div>
