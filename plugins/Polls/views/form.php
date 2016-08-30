<?php if (!defined('APPLICATION')) exit();

$Poll = $this->data('Poll');
$PollOptions = $this->data('PollOptions');

if (!$Poll) :
    echo wrap(t('Failed to load the poll.'), 'div class="Poll PollNotFound"');
else : // Display the poll
    ?>
    <div class="Poll PollForm Hero js-poll-form">
        <h2 class="PollQuestion"><?php echo Gdn_Format::plainText(val('Name', $Poll, '')); ?></h2>
        <div class="PollOptions">
            <?php
            $Form = new Gdn_Form();
            $Form->addHidden('PollID', val('PollID', $Poll));
            $Form->Action = url('discussion/pollvote');
            echo $Form->open();
            foreach ($PollOptions as $Option) {
                echo '<div class="PollOption">';
                echo $Form->radio('PollOptionID', '@'.Gdn_Format::to($Option['Body'], $Option['Format']), array('Value' => $Option['PollOptionID']));
                echo '</div>';
            }
            if (Gdn::session()->isValid()) {
                echo $Form->button('Vote', array('class' => 'Button Primary'));
            } else {
                $ReturnUrl = Gdn::request()->pathAndQuery();
                $AuthenticationUrl = signInUrl($ReturnUrl);
                $CssClass = c('Garden.SignIn.Popup') ? 'SignInPopup' : '';
                echo anchor(t('Sign in to vote!'), $AuthenticationUrl, $CssClass);
            }
            if (Gdn::session()->isValid()) {
                echo anchor(t('View Results'), '#', 'js-poll-result-btn');
            }

            echo $Form->close();
            echo '<div class="AnonymousWarning">';
            if (val('Anonymous', $Poll) == '0') {
                echo t('This is a public poll: others will see what you voted for.');
            } else {
                echo t('This is a private poll: no-one will see what you voted for.');
            }
            echo '</div>';
            ?>
        </div>
    </div>
<?php
endif;