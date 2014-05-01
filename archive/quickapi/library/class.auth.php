<?php if (!defined('APP')) exit;

/**
 * Auth handler
 * 
 * Inspects the request looking for access_token and checks it against the 
 * internal config setting for same. If they match, we authenticate.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package quickapi
 * @since 1.0
 */

class Auth {
   
   protected static $Authorized = NULL;
   
   /**
    * Inspect environment to see if we're authorized
    * 
    * @return void
    */
   public static function Authorize() {
      $tokenKey = C('auth token key', 'access_token');
      
      $token = C('auth token');
      if (!$token) return Auth::$Authorized = FALSE;
      $tokenSupplied = Request::$Current->Get($tokenName);
      
      if ($token != $tokenSupplied) return Auth::$Authorized = FALSE;
      return Auth::$Authorized = TRUE;
   }
   
   /**
    * Check if we're authorized
    * 
    * @return boolean
    */
   public static function Check() {
      if (is_null(Auth::$Authorized))
         Auth::Authorize();
      
      if (self::$Authorized) return TRUE;
      return FALSE;
   }
   
   /**
    * Signify that we require auth to proceed
    * 
    * @throws HTTPException
    */
   public static function Required() {
      if (!self::Check())
         throw new HTTPException('Not authorized', 401);
   }
   
}