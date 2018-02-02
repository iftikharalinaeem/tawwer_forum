<?php

class fireBasePlugin extends Gdn_OAuth2 {
    public function __construct() {
    }

    public function base_render_before($sender, $args) {
        include $sender->fetchViewLocation('firebase-ui', '', 'plugins/firebase');
   }

    /**
     * Inject a container into page for the buttons.
     *
     * @param $sender
     * @param $args
     */
   public function base_afterSignInButton_handler($sender, $args) {
        echo '
              <div id="firebaseui-auth-container"></div> 
            ';
   }

   // Elaborate this if you want to have a dedicated sign in page
   public function vanillaController_firebasesignin_create($sender, $args) {
       $sender->render('firebase-ui', '', 'plugins/firebase');
   }

   // This page will receieve the response from firebase with the user info
    public function vanillaController_firebaseconnect_create($sender, $args) {
        $sender->render('firebaseconnect', '', 'plugins/firebase');
    }
}
