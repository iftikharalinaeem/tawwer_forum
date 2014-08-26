<?php if (!defined('APPLICATION')) exit();
$Poll = $this->Data('Poll');
$PollOptions = $this->Data('PollOptions');
$Anonymous = GetValue('Anonymous', $Poll) || C('Plugins.Polls.AnonymousPolls');
$CountPollVotes = GetValue('CountVotes', $Poll);

if (!$Poll) {
    echo Wrap(T('Failed to load the poll.'), 'div class="Poll PollNotFound"');
} else {
    // Display the poll
    if (!$this->Data['UserHasVoted']) {
    ?>
        <div class="Poll PollForm Hero js-poll-form">
            <h2 class="PollQuestion"><?php
                echo Gdn_Format::PlainText(GetValue('Name', $Poll, ''));
                ?></h2>
            <div class="PollOptions">
                <?php
                $Form = new Gdn_Form();
                $Form->AddHidden('PollID', GetValue('PollID', $Poll));
                $Form->Action = Url('discussion/pollvote');
                echo $Form->Open();
                foreach ($PollOptions as $Option) {
                    echo '<div class="PollOption">';
                    echo $Form->Radio('PollOptionID', '@'.Gdn_Format::To($Option['Body'], $Option['Format']), array('Value' => $Option['PollOptionID']));
                    echo '</div>';
                }
                if (Gdn::Session()->IsValid()) {
                    echo $Form->Button('Vote', array('class' => 'Button Primary'));
                } else {
                    $ReturnUrl = Gdn::Request()->PathAndQuery();
                    $AuthenticationUrl = SignInUrl($ReturnUrl);
                    $CssClass = C('Garden.SignIn.Popup') ? 'SignInPopup' : '';
                    echo Anchor(T('Sign in to vote!'), $AuthenticationUrl, $CssClass);
                    echo ' ';
                }
                if (C('Plugins.Polls.ViewResultsBeforeVote', false)) {
                    if (!Gdn::Session()->IsValid() && C('Plugins.Polls.ViewResultsBlockGuests', false)) {

                    } else {
                        echo Anchor(T('View Results'), '#', 'js-poll-result-btn');
                    }

                }

                echo $Form->Close();
                echo '<div class="AnonymousWarning">';
                if (GetValue('Anonymous', $Poll) == '0')
                    echo T('This is a public poll: others will see what you voted for.');
                else
                    echo T('This is a private poll: no-one will see what you voted for.');
                echo '</div>';
                ?>
            </div>
        </div>

    <?php }
    $cssResultDisplay = 'none';
    if ($this->Data['UserHasVoted']) {
        $cssResultDisplay = 'block';
    }
    ?>
    <div class="Poll PollResults Hero js-poll-results" style="display: <?php echo $cssResultDisplay; ?>">
          <h2 class="PollQuestion"><?php
    //         echo Sprite('SpPoll');
             echo Gdn_Format::PlainText(GetValue('Name', $Poll, ''));
             echo ' '.Wrap(Plural(GetValue('CountVotes', $Poll), '%s vote', '%s votes'), 'span class="TotalVotes Gloss"');
          ?></h2>
    <div class="PollOptions">
        <?php
        $Item = 0;
        foreach ($PollOptions as $Option) {
            $Item = $Item + 1;
            $CountVotes = GetValue('CountVotes', $Option);
            $Votes = GetValue('Votes', $Option);
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
                        echo Wrap(Plural($CountVotes, '%s vote', '%s votes'), 'span class="Gloss VoteCount"');
                    ?>
                </div>
                <?php if (!$Anonymous && is_array($Votes) && $CountVotes > 0): ?>
                    <div class="VoteUsers">
                   <span class="PhotoGrid PhotoGridSmall">
                      <?php
                      $MaxLimit = C('Plugins.Polls.MaxShowVotes', 20);
                      $i = 0;
                      foreach ($Votes as $Vote) {
                          if ($i >= $MaxLimit)
                              break;

                          echo UserPhoto($Vote, array('Size' => 'Small'));
                          $i++;
                      }
                      ?>
                   </span>
                        <span class="VoteCount"><?php echo Plural($CountVotes, '%s vote', '%s votes'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php
        }
        ?>
    </div>

    <?php
    if (C('Plugins.Polls.ViewResultsBeforeVote', false) && !$this->Data['UserHasVoted']) {
        echo Anchor(T('Hide Results'), '#', 'js-poll-result-btn');
    }
    ?>

<?php
}
?>
