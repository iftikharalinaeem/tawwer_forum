<?php if (!defined('APPLICATION')) exit();
require_once Gdn::Controller()->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
foreach ($this->Data('Data', array()) as $Row): 
   $this->SetData('Record', $Row);
   $Body = Gdn_Format::To($Row['Body'], $Row['Format']);
   $CssClass = 'Item';
?>
<div id="<?php echo "{$Row['RecordType']}_{$Row['RecordID']}" ?>" class="<?php echo $CssClass; ?>">
   <div class="Item-Wrap">
      <div class="Item-Body">
         <div class="BodyWrap">
            <?php
            if ($Name = GetValue('Name', $Row)) {
               echo Wrap(
                  Anchor(Gdn_Format::Text($Name), $Row['Url']),
                  'h3', array('class' => 'Title'));
            }
            ?>
            <div class="Body Message">
               <?php
               echo $Body;
               unset($Body);
               ?>
            </div>
         </div>
      </div>
      <div class="Item-Footer">
         <div class="FooterWrap">
            <div class="AuthorWrap">
               <span class="Author">
                  <?php
                  echo UserPhoto($Row, array('Px' => 'Insert'));
                  echo UserAnchor($Row, array('Px' => 'Insert'));
                  ?>
               </span>
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
            <?php
            $RowObject = (object)$Row;
            WriteReactions($Row);
            ?>
         </div>
      </div>
   </div>
</div>
<?php 
endforeach;
