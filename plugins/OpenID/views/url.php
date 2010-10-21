<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->Data('Title'), '</h1>';
$Form = $this->Form; //new Gdn_Form();
$Form->Method = 'get';
echo $Form->Open();
echo $Form->Errors();
?>
<div class="Box">
<ul>
   <li>
      <?php
         echo $Form->Label('OpenID Url', 'Url');
         echo $Form->TextBox('Url', array('Name' => 'url'));
         echo ' ', $Form->Button('Go');
      ?>
   </li>
</ul>
</div>
<?php
echo $Form->Close();
?>