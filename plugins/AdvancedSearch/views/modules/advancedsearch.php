<?php if (!defined('APPLICATION')) exit(); ?>
<div class="AdvancedSearch <?php echo GetIncomingValue('adv') ? 'Open' : ''; ?>">
   <?php
   $Form = new Gdn_Form();
   $Form = $this->Form;
   echo $Form->Open(array('action' => Url('/search'))),
      $Form->Hidden('adv');
   
//   decho(Gdn::Controller()->Data('CalculatedSearch'), 'calc search');
   
   ?>
   <div class="KeywordsWrap InputAndButton">
      <?php
      echo $Form->TextBox('search', array('class' => 'InputBox BigInput', 'placeholder' => T('Search'), 'autocomplete' => 'off')).
         ' <a href="#" class="Handle" title="'.T('Advanced Search').'"><span class="Arrow"></span></a> '.
         '<span class="bwrap"><button type="submit" class="Button" title="'.T('Search').'">'.Sprite('SpSearch').'</button></span>';
      ?>
      <!--<div class="Gloss"><a href="#"><?php echo T('Search help'); ?></a></div>-->
   </div>
   <div class="AdvancedWrap">
      <?php if ($Discussion = Gdn::Controller()->Data('Discussion')): ?>
         <div class="P">
            <?php
            echo 
               $Form->Label('Discussion', 'discussionid', array('class' => 'Heading')).
               $Form->CheckBox('discussionid', Gdn_Format::Text(GetValue('Name', $Discussion)), array('nohidden' => TRUE, 'value' => GetValue('DiscussionID', $Discussion)));
            ?>
         </div>
      <?php endif; ?>
      <div class="P TitleRow<?php if ($Discussion) echo ' Hidden'; ?>">
         <?php
         echo $Form->Label('Title', 'title', array('class' => 'Heading')).
            $Form->TextBox('title', array('class' => 'InputBox BigInput'));
         ?>
      </div>
      <div class="P">
         <?php
         echo $Form->Label('Author', 'author', array('class' => 'Heading')).
            $Form->TextBox('author', array('class' => 'InputBox BigInput'));
         ?>
      </div>
      <div class="P">
         <?php
         echo $Form->Label('Category', 'cat', array('class' => 'Heading')).
            $Form->CategoryDropDown('cat', array('Permission' => 'view', 'Headings' => FALSE, 'IncludeNull' => array('all', T('(All)')), 'class' => 'BigInput'));
         ?>
         <div class="Checkboxes Inline">
            <?php
            echo $Form->CheckBox('subcats', T('search subcategories'), array('nohidden' => TRUE)).
               ' '.
               $Form->CheckBox('archived', T('search archived'), array('nohidden' => TRUE))
            ?>
         </div>
      </div>
      <?php if ($this->IncludeTags): ?>
      <div class="P">
         <?php
         echo $Form->Label('Tags', 'tags', array('class' => 'Heading')).
            $Form->TextBox('tags', array('class' => 'InputBox BigInput'));
         ?>
      </div>
      <?php endif; ?>
      <div class="P">
         <?php
         echo $Form->Label('What to search', '', array('class' => 'Heading'));
         echo '<div class="Inline">';
         foreach ($this->Types as $name => $label) {
            echo ' '.$Form->CheckBox($name, $label, array('nohidden' => true)).' ';
         }
         echo '</div>';
         ?>
      </div>
      <div class="P Inline">
         <?php
         echo $Form->Label('Date within', 'within').' '.
            $Form->DropDown('within', $this->DateWithinOptions).' '.
            $Form->Label('of', 'date').' '.
            $Form->TextBox('date', array('class' => 'InputBox DateBox')).
            ' <span class="Gloss">'.T('Date Examples', 'Examples: Monday, today, last week, Mar 26, 3/26/04').'</span>';
         ?>
      </div>
      <div class="P Buttons">
         <?php
         echo '<button type="submit" class="Button" title="'.T('Search').'">'.T('Search').'</button>';
         ?>
      </div>
   </div>
   <?php
   echo $Form->Close();
   ?>
</div>