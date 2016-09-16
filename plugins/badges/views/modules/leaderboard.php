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
     <?php echo panelHeading($this->title()); ?>
     <ul class="PanelInfo">
          <?php foreach ($this->Leaders as $Leader) : ?>
                <li>
                     <?php
                     $Username = val('Name', $Leader);
                     $Photo = val('Photo', $Leader);

                     echo anchor(
                          wrap(wrap(plural($Leader['Points'], '%s Point', '%s Points'), 'span', ['class' => 'Count']), 'span', ['class' => 'Aside']).' '
                              .wrap(
                                   img($Photo, ['class' => 'ProfilePhoto ProfilePhotoSmall']).' '.wrap(htmlspecialchars($Username), 'span', ['class' => 'Username']),
                                   'span',
                                   ['class' => 'Leaderboard-User']
                              ),
                          userUrl($Leader)
                     )
                     ?>
                </li>
          <?php endforeach; ?>
     </ul>
</div>
