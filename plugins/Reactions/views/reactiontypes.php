<?php if (!defined('APPLICATION')) exit; ?>
<style>
   .ActivateSlider {
    display: block;
    width: 80px;
    background: #001F44;
    border-radius: 2px;
    -moz-border-radius: 2px;
    -webkit-border-radius: 2px;
    box-shadow: 0 -5px 10px #0B64C6 inset;;
    -moz-box-shadow: 0 -5px 10px #0B64C6 inset;;
    -webkit-box-shadow: 0 -5px 10px #0B64C6 inset;;
   }

   .ActivateSlider-Active {
       text-align: right;
   }

   .ActivateSlider .SmallButton {
       margin: 0;
       min-width: 45px;
   }
   
   tr.InActive td {
      background: #efefef;
      color: #444;
   }
   
   tr.InActive img {
      opacity: .5;
   }
   
   .NameColumn {
      width: 175px;
   }
   
   tbody .NameColumn {
      font-size: 15px;
      font-weight: bold;
      vertical-align: middle;
   }
   
   tbody .NameColumn img {
      vertical-align: middle;
   }
   
   tbody .AutoDescription {
      font-size: 11px;
      margin: 10px 0 0;
      border-top: solid 1px #ddd;
      padding: 5px 0 0 0;
   }
</style>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Introducing Vanilla Reactions and Badges"), 'http://vanillaforums.com/blog/news/introducing-vanilla-reactions-and-badges'), 'li');
   echo '</ul>';
   ?>
</div>

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info PageInfo">
   <p><b>Heads up!</b> Here are all of the reactions you can use on your site.
      Which reactions you use really depends on your community, but we recommend keeping a couple of points in mind.
   </p>
   <ol>
      <li>
         Try and have a mix of good and bad reactions.
      </li>
      <li>
         Don't use too many reactions. You don't  want to give your users information overload.
      </li>
   </ol>
</div>
<table id="Badges" class="AltColumns ManageBadges">
   <thead>
      <tr>
         <th class="NameColumn"><?php echo T('Reaction'); ?></th>
         <th><?php echo T('Description'); ?></th>
         <th><?php echo T('Actions Based on Votes'); ?></th>
         <th class="Options"><?php echo T('Active'); ?></th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($this->Data('ReactionTypes') as $ReactionType): ?>
      <?php
      $State = $ReactionType['Active'] ? 'Active' : 'InActive';
      ?>
      <tr id="ReactionType_<?php echo $ReactionType['UrlCode']; ?>" class="<?php echo $State; ?>">
         <td class="NameColumn"><div class="CellWrap">
            <?php
            echo Img('http://badges.vni.la/reactions/50/'.strtolower($ReactionType['UrlCode']).'.png', array('ReactionImage')), ' ';
            echo T($ReactionType['Name']);
            ?></div>
         </td>
         <td><?php echo $ReactionType['Description']; ?></td>
         <td>
            <?php            
            $AutoDescription = implode(' ', AutoDescription($ReactionType));
            if ($AutoDescription) 
               echo $AutoDescription;
            ?>
         </td>
         <td>
            <?php
            echo ActivateButton($ReactionType);
            ?>
         </td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>