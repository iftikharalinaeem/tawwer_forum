<?php if (!defined('APP')) exit;

/**
 * API God Object
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package quickapi
 * @since 1.0
 */

class Api {
   
   /**
    * @var Config
    */
   public static $Config;
   
   public static function Configure() {
      Api::$Config = new Config(P(PATH_ROOT,'config.ini'));
   }
   
   /**
    * Dispatcher
    * 
    * @param string|null $request
    * @return View
    */
   public static function Dispatch($request = null) {
      // Contruct the request.
      if ($request === null)
         $request = new Request($_GET['p'], $_GET, $_POST);
      elseif (is_string($request))
         $request = new Request($request);

      $request = Route($request);

      Request::$Current = $request;

      $className = ucfirst($request->Path(0)).'Controller';
      $methodName = ucfirst($request->Path(1));

      $controller = new $className();

      $reflectArgs = ReflectArgs($request, $className, $methodName);

      try {
         call_user_func_array(array($controller, $methodName), $reflectArgs);
         
         $view = $controller->Data();
         unset($controller);
      } catch (HTTPException $ex) {
         Controller::Status($ex->getCode(), $ex->getMessage());
         $view = array(
            'Exception' => $ex->getMessage(),
            'Code'      => $ex->getCode()
         );
      } catch (Exception $ex) {
         Controller::Status(500);
         $view = array(
            'Exception' => $ex->getMessage(),
            'Code'      => 500
         );
      }
      
      Api::Render($view);
   }
   
   /**
    * Render the output
    * 
    * @param type $view
    */
   public static function Render($view) {
      if ($view)
         echo json_encode($view);
   }
}

class HTTPException extends Exception {}