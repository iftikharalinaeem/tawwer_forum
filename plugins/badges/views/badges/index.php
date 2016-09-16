<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t('All Badges'); ?></h1>

<ul class="DataList Badges">
<?php 
$UserModel = new UserModel();

foreach ($this->data('Badges') as $Badge) :
    $GivenByUserID = val('GivenByUserID', $Badge, null);
    $GivenByUser = $GivenByUserID ? $UserModel->getID($GivenByUserID) : null; ?>

    <li class="Item ItemBadge<?php if (!$GivenByUser) echo ' Read'; ?>">
        <?php
        if ($Badge['Photo']) {
            echo img(Gdn_Upload::url($Badge['Photo']), ['height' => '40px', 'width' => '40px', 'class' => 'BadgePhoto']);
        }
        ?>
        <span class="Options">
            <?php
            //echo OptionsList($Discussion);
            ?>
        </span>
        <div class="ItemContent Badge">
            <div class="Title">
                <?php echo anchor($Badge['Name'], 'badge/'.$Badge['Slug'], 'Title'); ?>
            </div>
            <div class="Meta">
                <?php if (val('Body', $Badge)) : ?>
                <span class="MItem BadgeDescription"><?php echo Gdn_Format::text($Badge['Body']); ?></span>
                <?php endif; ?>
                <span class="MItem BadgePoints"><?php echo sprintf(t('%s points.'), Gdn_Format::text($Badge['Points'])); ?></span>
                <?php if ($GivenByUser) : ?>
                <p><?php
                    echo sprintf(t('You earned this %s from %s'),
                        Gdn_Format::date(val('DateGiven', $Badge)),
                        Gdn_Format::text(val('Name', $GivenByUser))
                    );
                    if ($Reason = val('Reason', $Badge)) {
                        echo ': &ldquo;'.Gdn_Format::text(val('Reason', $Badge)).'&rdquo;';
                    }
                ?></p>
                <?php endif; ?>
            </div>
        </div>
    </li>

<?php endforeach; ?>
</ul>