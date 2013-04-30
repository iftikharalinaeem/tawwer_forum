<?php if (!defined('APPLICATION')) exit(); 
$Poll = $this->Data('Poll');
$PollOptions = $this->Data('PollOptions');
if (!$Poll):
   echo Wrap(T('Failed to load the poll.'), 'div class="Poll PollNotFound"');
else:
   // Display the poll
   ?>
   <div class="Poll PollForm Hero">
      <div class="PollQuestion"><?php 
         echo Sprite('SpPoll'); 
         echo Gdn_Format::PlainText(GetValue('Name', $Poll, ''));
      ?></div>
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
         if (Gdn::Session()->IsValid())
            echo $Form->Button('Vote', array('class' => 'Button Primary'));
         else {
            $ReturnUrl = Gdn::Request()->PathAndQuery();
            $AuthenticationUrl = SignInUrl($ReturnUrl); 
            $CssClass = C('Garden.SignIn.Popup') ? 'SignInPopup' : '';
            echo Anchor(T('Sign in to vote!'), $AuthenticationUrl, $CssClass);
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
<?php
endif;