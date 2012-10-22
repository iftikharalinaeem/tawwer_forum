<?php if (!defined('APPLICATION')) exit(); ?>

<ul class="DataList Compact BlogList">
   <?php 
   foreach ($this->Data('Data', array()) as $Row): 
      $this->SetData('Record', $Row);
   ?>
   <li id="<?php echo "{$Row['RecordType']}_{$Row['RecordID']}" ?>" class="Item">
      <?php
      if ($Name = GetValue('Name', $Row)) {
         echo Wrap(
            Anchor(Gdn_Format::Text($Name), $Row['Url']),
            'h3', array('class' => 'Title'));
      }
      ?>
      <div class="Item-Header">
         <div class="AuthorWrap">
            <span class="Author">
               <?php
               echo UserPhoto($Row, array('Px' => 'Insert'));
               echo UserAnchor($Row, array('Px' => 'Insert'));
               ?>
            </span>
<!--            <span class="AuthorInfo">
               <?php
               //echo WrapIf(GetValue('Title', $Author), 'span', array('class' => 'MItem AuthorTitle'));
               $this->FireEvent('AuthorInfo'); 
               ?>
            </span>-->
         </div>
         <div class="Meta">
            <span class="MItem DateCreated">
               <?php
               echo Anchor(
                  Gdn_Format::Date($Row['DateInserted'], 'html'),
                  $Row['Url'],
                  'Permalink'
                  );
               ?>
            </span>
         </div>
      </div>
      
      <div class="Item-BodyWrap">
         <div class="Item-Body">
            <div class="Message Expander">
               <?php
               echo Gdn_Format::To($Row['Body'], $Row['Format']);
               ?>
            </div>
         </div>
      </div>
      
      <?php
      $RowObject = (object)$Row;
      Gdn::Controller()->EventArguments['Object'] = $RowObject;
      Gdn::Controller()->EventArguments[$Row['RecordType']] = $RowObject;
      Gdn::Controller()->FireAs('DiscussionController')->FireEvent("After{$Row['RecordType']}Body");
      
      WriteReactions($Row);
      ?>
   </li>
   <?php endforeach; ?>
</ul>
<?php
echo PagerModule::Write();
?>