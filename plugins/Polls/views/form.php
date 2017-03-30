<?php if (!defined('APPLICATION')) exit();

$poll = $this->data('Poll');
$pollOptions = $this->data('PollOptions');

if (!$poll) :
    echo wrap(t('Failed to load the poll.'), 'div class="Poll PollNotFound"');
else : // Display the poll
    ?>
    <div class="Poll PollForm Hero js-poll-form">
        <h2 class="PollQuestion"><?php echo Gdn_Format::plainText(val('Name', $poll, '')); ?></h2>
        <div class="PollOptions">
            <?php
            $form = new Gdn_Form();
            $form->addHidden('PollID', val('PollID', $poll));
            $form->Action = url('discussion/pollvote');
            echo $form->open();
            foreach ($pollOptions as $option) {
                echo '<div class="PollOption">';
                echo $form->radio('PollOptionID', '@'.Gdn_Format::to($option['Body'], $option['Format']), array('Value' => $option['PollOptionID']));
                echo '</div>';
            }
            if (Gdn::session()->isValid()) {
                echo $form->button('Vote (action)', ['class' => 'Button Primary', 'value' => 'Vote']);
            } else {
                $returnUrl = Gdn::request()->pathAndQuery();
                $authenticationUrl = signInUrl($returnUrl);
                $cssClass = c('Garden.SignIn.Popup') ? 'SignInPopup' : '';
                echo anchor(t('Sign in to vote!'), $authenticationUrl, $cssClass);
            }
            if (Gdn::session()->isValid()) {
                echo anchor(t('View Results'), '#', 'js-poll-result-btn');
            }

            echo $form->close();
            echo '<div class="AnonymousWarning">';
            if (val('Anonymous', $poll) == '0') {
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
