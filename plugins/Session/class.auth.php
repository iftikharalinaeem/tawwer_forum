<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class Gdn_Auth {
   /// PROPERTIES ///


   /// METHODS ///

   public function RegisterUrl($Target = '/') {
      return '/entry/register?Target='.urldecode($Target);
   }

   public function SetIdentity($Value, $Persist = FALSE) {
      Deprecated(__Function__, 'Gdn_Session::Start()');
      Gdn::Session()->Start($Value, TRUE, $Persist);
   }

   public function SignInUrl($Target = '/') {
      return '/entry/signin?Target='.urlencode($Target);
   }

   public function SignOutUrl($Target = '/') {
      $Query = array('TransientKey' => Gdn::Session()->TransientKey(), 'Target' => $Target);
      return '/entry/signout?'.http_build_query($Query);
   }

   public function StartAuthenticator() {
      Gdn::Session()->Initialize();
   }
}