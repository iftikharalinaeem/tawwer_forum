<?php if (!defined('APP')) return;

class HomeController extends Controller {

   public function NotFound($url) {
      header('Not Found', TRUE, 404);
   }
   
}