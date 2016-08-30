<?php if (!defined('APPLICATION')) exit();

$Poll = $this->data('Poll');
$Anonymous = val('Anonymous', $Poll) || c('Plugins.Polls.AnonymousPolls');
$CountPollVotes = val('CountVotes', $Poll);
$PollOptions = $this->data('PollOptions');

$cssResultDisplay = 'style="display: none"';
if ($this->Data['UserHasVoted']) {
    $cssResultDisplay = '';
}

if (!$Poll) :
    echo wrap(t('Failed to load the poll.'), 'div class="Poll PollNotFound"');
else : // Display the poll
    ?>
    <div class="Poll PollResults Hero js-poll-results" <?php echo $cssResultDisplay; ?>>
        <h2 class="PollQuestion"><?php
            echo Gdn_Format::plainText(val('Name', $Poll, ''));
            echo ' '.wrap(plural(val('CountVotes', $Poll), '%s vote', '%s votes'), 'span class="TotalVotes Gloss"');
            ?></h2>
        <div class="PollOptions">
            <?php
            $Item = 0;
            foreach ($PollOptions as $Option) {
                $Item = $Item + 1;
                $CountVotes = val('CountVotes', $Option);
                $Votes = val('Votes', $Option);
                $Percent = $CountPollVotes > 0 ? floor(($CountVotes * 100) / $CountPollVotes) : 0;
                ?>
                <div class="PollOption PollOption<?php echo $Item; ?>">
                    <div class="VoteOption"><?php echo Gdn_Format::to(val('Body', $Option, ''), val('Format', $Option, 'Text')); ?></div>
                    <div class="VoteBar">
                        <div class="VoteBarBG"></div>
                        <span class="VoteBarWidth PollColor PollColor<?php echo $Item; ?>" style="width: <?php echo $Percent; ?>%"></span>
                        <span class="VotePercent"><?php echo $Percent; ?>%</span>
                        <?php
                        if ($Anonymous) {
                            echo wrap(plural($CountVotes, '%s vote', '%s votes'), 'span class="Gloss VoteCount"');
                        }
                        ?>
                    </div>
                    <?php if (!$Anonymous && is_array($Votes) && $CountVotes > 0) : ?>
                        <div class="VoteUsers">
                            <span class="PhotoGrid PhotoGridSmall">
                            <?php
                            $MaxLimit = c('Plugins.Polls.MaxShowVotes', 20);
                            $i = 0;
                            foreach ($Votes as $Vote) {
                                if ($i >= $MaxLimit) {
                                    break;
                                }
                                echo userPhoto($Vote, ['Size' => 'Small']);
                                $i++;
                            }
                            ?>
                            </span>
                            <span class="VoteCount"><?php echo plural($CountVotes, '%s vote', '%s votes'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php
            }
            ?>
        </div>
        <?php
        if (!$this->Data['UserHasVoted']) {
            echo anchor(t('Hide Results'), '#', 'js-poll-result-btn');
        }
        ?>
    </div>
<?php
endif;