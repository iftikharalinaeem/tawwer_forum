<?php if (!defined('APPLICATION')) exit(); ?>
<style>
    .PanelInfo li {
        clear: both;
    }
    .PhotoWrapSmall {
        margin-right: 5px;
        display: inline-block;
    }
</style>
<div class="Box Leaderboard">
    <h4><?php echo $this->Title(); ?></h4>
    <ul class="PanelInfo">
        <?php foreach ($this->Leaders as $Leader) : ?>
            <li>
                <?php
                $Username = GetValue('Name', $Leader);
                $Photo = GetValue('Photo', $Leader);

                echo Anchor(
                    Wrap(Wrap(Plural($Leader['Points'], '%s Point', '%s Points'), 'span', array('class' => 'Count')), 'span', array('class' => 'Aside')).' '.
                    Wrap(
                        Img($Photo, array('class' => 'ProfilePhoto ProfilePhotoSmall')).' '.
                        Wrap(htmlspecialchars($Username), 'span', array('class' => 'Username'))
                    , 'span', array('class' => 'Leaderboard-User')),
                    UserUrl($Leader)
                )
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
