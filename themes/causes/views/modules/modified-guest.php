<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo T('Howdy, Stranger!'); ?></h4>
   <p><?php echo "It looks like you're new here. If you want to get involved, sign in with Facebook below!"; ?></p>
   <p>
      <?php
      try {
         $FB = Gdn::PluginManager()->GetPluginInstance('FacebookPlugin');
         $ImgSrc = Asset('/plugins/Facebook/design/facebook-login.png');
         $ImgAlt = T('Login with Facebook');
         $SigninHref = $FB->AuthorizeUri();
         $PopupSigninHref = $FB->AuthorizeUri('display=popup');
         echo "<a id=\"FacebookAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" align=\"bottom\" /></a>";
      } catch(Exception $ex) {
      }
      ?>
   </p>
</div>