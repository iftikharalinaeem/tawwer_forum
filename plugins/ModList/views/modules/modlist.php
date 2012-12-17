<?php if (!defined('APPLICATION')) exit; ?>
<div id="ModList" class="ModList Box">
   <h2 class="H"><?php echo T('Category Moderators', 'Moderators'); ?></h2>
   <div class="Moderators"><?php 
         
      if ($this->Style == 'pictures') {
         
         $ListClass = 'PhotoGrid PhotoGridSmall';
         if (count($this->Data('Moder')) > 10)
            $ListClass .= ' PhotoGridSmall';

         echo '<div class="'.$ListClass.'">'."\n";
         foreach ($this->Data('Moderators') as $User) {
            $WrapClass = array('CategoryModeratorWrap', 'UserPicture');

            if (!$User['Photo'] && !function_exists('UserPhotoDefaultUrl'))
               $User['Photo'] = Asset('/applications/dashboard/design/images/usericon.gif', TRUE);

            $WrapClass = implode(' ', $WrapClass);
            echo " <span class=\"{$WrapClass}\">";
            echo UserPhoto($User, array('Size' => 'Small'));

            $UserName = GetValue('Name', $User, FALSE);
            if ($UserName)
               echo ' '.UserAnchor($User, 'Username CategoryModeratorName');
            echo '</span> ';
         }
         echo '</div>'."\n";

      } else {

         echo '<ul class="PanelInfo">'."\n";
         foreach ($this->Data('Moderators') as $User) {
            $WrapClass = array('CategoryModeratorWrap', 'UserLink');

            $WrapClass = implode(' ', $WrapClass);
            echo "<li class=\"{$WrapClass}\">".UserAnchor($User)."</li>\n";
         }
         echo '</ul>'."\n";

      }
      
   ?></div>
</div>