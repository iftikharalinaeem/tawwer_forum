<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Poll">
   <div class="PollQuestion"><?php echo Sprite('SpPoll'); ?>Where do you get your coffee?</div>
   <div class="PollAnswers">
      <?php
      $TotalVotes = $TVotes = 100;
      $Answers = Gdn::Controller()->Data('Answers');
      foreach ($Answers as $Key => $Answer) {
         $Item = $Key+1;
         $Votes = $Item == count($Answers) ? $TVotes : rand(0, $TVotes);
         $TVotes = $TVotes - $Votes;
         $Percent = floor(($Votes * 100) / $TotalVotes);
      ?>
      <div class="PollAnswer PollAnswer<?php echo $Item; ?>">
         <div class="VoteAnswer"><?php echo $Answer; ?></div>
         <div class="VoteBar">
            <div class="VoteBarBG"></div>
            <span class="VoteBarWidth PollColor PollColor<?php echo $Item; ?>" style="width: <?php echo $Percent; ?>%"></span>
            <span class="VotePercent"><?php echo $Percent; ?>%</span>
         </div>
         <?php if ($Votes > 0): ?>
         <div class="VoteUsers">
            <span class="PhotoGrid PhotoGridSmall">
               <?php 
               $MaxLimit = 20;
               $Max = $Votes > $MaxLimit ? $MaxLimit : $Votes;
               for ($i = 0; $i < $Max; $i++) {
                  $UserID = rand(2, 100);
                  $User = new stdClass();
                  $User->UserID = $UserID;
                  $User->Photo = '';
                  $User->Name = 'user'.$UserID;
                  $User->Email = $User->Name.'@vanillaforums.com';
                  echo UserPhoto($User);
               }
               ?>
            </span>
            <span class="VoteCount"><?php echo Plural($Votes, '%s vote', '%s votes'); ?></span>
         </div>
         <?php endif; ?>
      </div>
      <?php
      }
      ?>
   </div>
</div>