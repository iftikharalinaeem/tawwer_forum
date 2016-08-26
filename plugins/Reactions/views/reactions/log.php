<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Reactions'); ?></h1>
<table class="DataTable DataTable-ReactionsLog">
   <tbody>
      <?php
      foreach ($this->Data('UserTags') as $Row): 
      ?>
      <tr class="Item">
         <td class="ReactionsLog-DateCol"><?php echo Gdn_Format::Date($Row['DateInserted']); ?></td>
         <td class="ReactionsLog-UserCol"><?php echo UserAnchor($Row); ?></td>
         <td class="ReactionsLog-ReactionCol">
            <?php
            $ReactionType = ReactionModel::FromTagID($Row['TagID']);
            echo htmlspecialchars($ReactionType['Name']);
            ?>
         </td>
         <td class="ReactionsLog-OptionsCol">
            <div class="Options">
            <?php
            echo Anchor('&times;', "/reactions/undo/{$this->Data['RecordType']}/{$this->Data['RecordID']}/{$ReactionType['UrlCode']}?userid={$Row['UserID']}&tagid={$Row['TagID']}", 'TextColor Hijack',
               array('title' => sprintf(T('Remove %s'), T('reaction'))));
            ?>
            <div>
         </td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
<?php
//decho($this->Data);
?>