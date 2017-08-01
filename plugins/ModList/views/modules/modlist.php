<?php if (!defined('APPLICATION')) exit; ?>
<div id="ModList" class="ModList Box">
   <h2 class="H"><?php echo t('Category Moderators', 'Moderators'); ?></h2>
   <div class="Moderators"><?php 
         
      if ($this->Style == 'pictures') {
         
         $ListClass = 'PhotoGrid PhotoGridSmall';
         if (count($this->data('Moder')) > 10)
            $ListClass .= ' PhotoGridSmall';

         echo '<div class="'.$ListClass.'">'."\n";
         foreach ($this->data('Moderators') as $User) {
            $WrapClass = ['CategoryModeratorWrap', 'UserPicture'];

            if (!$User['Photo'] && !function_exists('UserPhotoDefaultUrl'))
               $User['Photo'] = asset('/applications/dashboard/design/images/usericon.gif', TRUE);

            $WrapClass = implode(' ', $WrapClass);
            echo " <span class=\"{$WrapClass}\">";
            echo userPhoto($User, ['Size' => 'Small']);

            $UserName = getValue('Name', $User, FALSE);
            if ($UserName)
               echo ' '.userAnchor($User, 'Username CategoryModeratorName', ['title' => $UserName]);
            echo '</span> ';
         }
         echo '</div>'."\n";

      } else {

         echo '<ul class="PanelInfo">'."\n";
         foreach ($this->data('Moderators') as $User) {
            $WrapClass = ['CategoryModeratorWrap', 'UserLink'];

            $WrapClass = implode(' ', $WrapClass);
            echo "<li class=\"{$WrapClass}\">".userAnchor($User)."</li>\n";
         }
         echo '</ul>'."\n";

      }
      
   ?></div>
</div>