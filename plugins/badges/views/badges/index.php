<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo T('All Badges'); ?></h1>

<ul class="DataList Badges">
<?php 
$UserModel = new UserModel();
foreach ($this->Data('Badges') as $Badge) :
    $GivenByUserID = GetValue('GivenByUserID', $Badge, NULL);
    $GivenByUser = $GivenByUserID ? $UserModel->GetID($GivenByUserID) : NULL; ?>

    <li class="Item ItemBadge<?php if (!$GivenByUser) echo ' Read'; ?>">
        <?php if ($Badge['Photo']) : ?>
            <?php echo Img(Gdn_Upload::Url($Badge['Photo']), array('height' => '40px', 'width' => '40px', 'class' => 'BadgePhoto')); ?>
        <?php endif; ?>
        <span class="Options">
            <?php
            //echo OptionsList($Discussion);
            ?>
        </span>
        <div class="ItemContent Badge">
            <div class="Title">
                <?php echo Anchor($Badge['Name'], 'badge/'.$Badge['Slug'], 'Title'); ?>
            </div>
            <div class="Meta">
                <?php if (GetValue('Body', $Badge)) : ?>
                <span class="MItem BadgeDescription"><?php echo Gdn_Format::Text($Badge['Body']); ?></span>
                <?php endif; ?>
                <span class="MItem BadgePoints"><?php echo sprintf(T('%s points.'), Gdn_Format::Text($Badge['Points'])); ?></span>
                <?php if ($GivenByUser) : ?>
                <p><?php
                    echo sprintf(T('You earned this %s from %s'),
                        Gdn_Format::Date(GetValue('DateGiven', $Badge)),
                        Gdn_Format::Text(GetValue('Name', $GivenByUser)));
                    if ($Reason = GetValue('Reason', $Badge))
                        echo ': &ldquo;'.Gdn_Format::Text(GetValue('Reason', $Badge)).'&rdquo;';
                ?></p>
                </div>
            <?php endif; ?>
        </div>
    </li>

<?php endforeach; ?>
</ul>