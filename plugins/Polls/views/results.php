<?php if (!defined('APPLICATION')) exit();

$poll = $this->data('Poll');
$anonymous = val('Anonymous', $poll) || c('Plugins.Polls.AnonymousPolls');
$countPollVotes = val('CountVotes', $poll);
$pollOptions = $this->data('PollOptions');

$cssResultDisplay = $this->showForm() ? 'style="display: none"' : '';

if (!$poll) :
    echo wrap(t('Failed to load the poll.'), 'div class="Poll PollNotFound"');
else : // Display the poll
    ?>
    <div class="Poll PollResults Hero js-poll-results" <?php echo $cssResultDisplay; ?>>
        <h2 class="PollQuestion"><?php
            echo Gdn_Format::plainText(val('Name', $poll, ''));
            echo ' '.wrap(plural(val('CountVotes', $poll), '%s vote', '%s votes'), 'span class="TotalVotes Gloss"');
            ?></h2>
        <div class="PollOptions">
            <?php
            $item = 0;
            foreach ($pollOptions as $option) {
                $item = $item + 1;
                $countVotes = val('CountVotes', $option);
                $votes = val('Votes', $option);
                $percent = $countPollVotes > 0 ? floor(($countVotes * 100) / $countPollVotes) : 0;
                ?>
                <div class="PollOption PollOption<?php echo $item; ?>">
                    <div class="VoteOption"><?php echo Gdn_Format::to(val('Body', $option, ''), val('Format', $option, 'Text')); ?></div>
                    <div class="VoteBar">
                        <div class="VoteBarBG"></div>
                        <span class="VoteBarWidth PollColor PollColor<?php echo $item; ?>" style="width: <?php echo $percent; ?>%"></span>
                        <span class="VotePercent"><?php echo $percent; ?>%</span>
                        <?php
                        if ($anonymous) {
                            echo wrap(plural($countVotes, '%s vote', '%s votes'), 'span class="Gloss VoteCount"');
                        }
                        ?>
                    </div>
                    <?php if (!$anonymous && is_array($votes) && $countVotes > 0) : ?>
                        <div class="VoteUsers">
                            <span class="PhotoGrid PhotoGridSmall">
                            <?php
                            $maxLimit = c('Plugins.Polls.MaxShowVotes', 20);
                            $i = 0;
                            foreach ($votes as $vote) {
                                if ($i >= $maxLimit) {
                                    break;
                                }
                                echo userPhoto($vote, ['Size' => 'Small']);
                                $i++;
                            }
                            ?>
                            </span>
                            <span class="VoteCount"><?php echo plural($countVotes, '%s vote', '%s votes'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php
            }
            ?>
        </div>
        <?php
        if ($this->showForm()) {
            echo anchor(t('Hide Results'), '#', 'js-poll-result-btn');
        }
        ?>
    </div>
<?php
endif;
