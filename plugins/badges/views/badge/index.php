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
$Session = Gdn::Session();

$this->Title(T('View Badge') . ': ' . $this->Data('Badge.Name')); ?>

<?php if ($Photo = $this->Data('Badge.Photo'))
    echo Img( Gdn_Upload::Url(ChangeBasename($Photo, '%s')), array('class' => 'BadgePhotoDisplay') ); ?>

<h1><?php echo Gdn_Format::Text($this->Data('Badge.Name')); ?></h1>
<p><?php echo Gdn_Format::Html($this->Data('Badge.Body')); ?></p>
</div>

<div class="Badge-Earned">
<?php if ($this->Data('UserBadge.DateCompleted')) : ?>
<div class="EarnedThisBadge">
    <?php echo UserPhoto(Gdn::Session()->User); echo T('YouEarnedBadge', 'You earned this badge'); ?>
    <span class="DateReceived"><?php echo Gdn_Format::Date($this->Data('UserBadge.DateCompleted'), 'html'); ?></span>
</div>
<?php endif; ?>

<?php if ((count($this->Data('Recipients')) > 0) && ($Count = $this->Data('Badge.CountRecipients'))) : ?>

    <p class="BadgeCountDisplay"><?php
        echo Plural($Count, T('%s person has earned this badge.'), T('%s people have earned this badge.'));
    ?></p>

    <h2><?php echo T('BadgeRecipientsHeading', "Most recent recipients"); ?></h2>
    <div class="RecentRecipients">
        <?php foreach($this->Data('Recipients', array()) as $User) : ?>
        <div class="CellWrap"><div class="Cell"><?php
            echo UserPhoto($User);
            echo UserAnchor($User);
        ?> <span class="DateReceived"><?php echo Gdn_Format::Date(GetValue('DateCompleted', $User), 'html'); ?> </span>
            </div></div>
        <?php endforeach; ?>
    </div>

<?php else : ?>

    <p><?php echo T('BadgesNobody', 'Nobody has earned this badge yet.'); ?></p>

<?php endif; ?>
</div>
