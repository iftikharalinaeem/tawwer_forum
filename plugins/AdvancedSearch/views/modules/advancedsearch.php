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
         ' <a href="#" class="Handle" title="'.T('Advanced Search').'"></a> '.
         '<button type="submit" class="Button" title="'.T('Search').'">'.Sprite('SpSearch').'</button>';
      ?>
   </div>
   <div class="AdvancedWrap">
      <div class="P">
         <?php
         echo $Form->Label('Title', 'title').
            $Form->TextBox('title', array('class' => 'InputBox BigInput'));
         ?>
      </div>
      <div class="P">
         <?php
         echo $Form->Label('Author', 'author').
            $Form->TextBox('author', array('class' => 'InputBox BigInput'));
         ?>
      </div>
      <div class="P">
         <?php
         echo $Form->Label('Category', 'categoryid').
            $Form->CategoryDropDown('categoryid', array('Permission' => 'view', 'Headings' => FALSE, 'IncludeNull' => T('(All)'), 'class' => 'BigInput'));
         ?>
      </div>
      <?php if ($this->IncludeTags): ?>
      <div class="P">
         <?php
         echo $Form->Label('Tags', 'tags').
            $Form->TextBox('tags', array('class' => 'InputBox BigInput'));
         ?>
      </div>
      <?php endif; ?>
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