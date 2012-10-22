<?php if (!defined('APPLICATION')) exit();

// Register the OAuth 2 authenticator.
Gdn::Authenticator()->RegisterAuthenticator('oauth2');