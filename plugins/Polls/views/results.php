<?php if (!defined('APPLICATION')) exit(); 
$Poll = $this->Data('Poll');
$Anonymous = GetValue('Anonymous', $Poll);
$CountPollVotes = GetValue('CountVotes', $Poll);
$PollOptions = $this->Data('PollOptions');
if (!$Poll):
   echo Wrap(T('Failed to load the poll.'), 'div class="Poll PollNotFound"');
else:
   // Display the poll
   ?>
   <div class="Poll PollResults Hero">
      <div class="PollQuestion"><?php 
         echo Sprite('SpPoll'); 
         echo Gdn_Format::PlainText(GetValue('Name', $Poll, ''));
         echo Wrap(Plural(GetValue('CountVotes', $Poll), '%s vote', '%s votes'), 'span class="TotalVotes"');
      ?></div>
      <div class="PollOptions">
         <?php
         $Item = 0;
         foreach ($PollOptions as $Option) {
            $Item = $Item + 1;
            $CountVotes = GetValue('CountVotes', $Option);
            $Votes = GetValue('Votes', $Option);
            $CountActualVotes = count($Votes);
            $Percent = $CountPollVotes > 0 ? floor(($CountVotes * 100) / $CountPollVotes) : 0;
         ?>
         <div class="PollOption PollOption<?php echo $Item; ?>">
            <div class="VoteOption"><?php echo Gdn_Format::To(GetValue('Body', $Option, ''), GetValue('Format', $Option, 'Text')); ?></div>
            <div class="VoteBar">
               <div class="VoteBarBG"></div>
               <span class="VoteBarWidth PollColor PollColor<?php echo $Item; ?>" style="width: <?php echo $Percent; ?>%"></span>
               <span class="VotePercent"><?php echo $Percent; ?>%</span>
               <?php
               if ($Anonymous)
                  echo Wrap(Plural($CountActualVotes, '%s vote', '%s votes'), 'span class="VoteCount"');
               ?>
            </div>
            <?php if (!$Anonymous && is_array($Votes) && $CountActualVotes > 0): ?>
            <div class="VoteUsers">
               <span class="PhotoGrid PhotoGridSmall">
                  <?php 
                  $MaxLimit = 20;
                  $Max = $CountActualVotes > $MaxLimit ? $MaxLimit : $CountActualVotes;
                  for ($i = 0; $i < $Max; $i++)
                     echo UserPhoto($Votes[$i], array('Size' => 'Small'));
                  ?>
               </span>
               <span class="VoteCount"><?php echo Plural($CountActualVotes, '%s vote', '%s votes'); ?></span>
            </div>
            <?php endif; ?>
         </div>
         <?php
         }
         ?>
      </div>
   </div>
<?php
endif;