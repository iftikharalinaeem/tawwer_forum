<?php
/**
 * @copyright 2015 Vanilla Forums, Inc.
 */

$PluginInfo['burnerblocker'] = array(
    'Name' => 'Burner Blocker',
    'Description' => 'Blocks all known "burner" email domains from registering. A master list is maintained by Vanilla.',
    'Version' => '1.0',
    'Author' => "Lincoln Russell",
    'AuthorEmail' => 'lincoln@vanillaforums.com',
    'MobileFriendly' => true,
    'License' => 'Proprietary'
);

/**
 * Class BurnerBlockerPlugin
 */
class BurnerBlockerPlugin extends Gdn_Plugin {

    /** @var array Add all known burner domains here. DO NOT SHARE. */
    protected $bannedDomains = array(
        '139.com',
        'daum.net',
        'discardmail.com',
        'fakeinbox.com',
        'grr.la',
        'guerrillamail.biz',
        'guerrillamail.com',
        'guerrillamail.de',
        'guerrillamail.net',
        'guerrillamail.org',
        'guerrillamailblock.com',
        'hanmail.net',
        'incognitomail.org',
        'mailinator.com',
        'mailnesia.com',
        'mail.ru',
        'mt2015.com',
        'nowmymail.com',
        'sharklasers.com',
        'slipry.net',
        'spam4.me',
        'trashmail.com',
        'trashmail.ws',
        'yopmail.com',
    );

    /**
     * Block registration of users with a known burner email domain.
     *
     * @param $sender UserModel Triggering object.
     * @param $args array Event arguments.
     */
    public function userModel_beforeRegister_handler($sender, &$args) {
        // Get the user's email domain.
        $email = val('Email', $args['RegisteringUser']);
        $domain = substr($email, strpos($email, '@')+1);

        // Check the user's email domain against our private list.
        if (in_array($domain, $this->bannedDomains)) {
            // Block the registration.
            $args['Valid'] = false;
            // Provide matching error as if a ban rule was invoked.
            $sender->Validation->addValidationResult('UserID', 'Sorry, permission denied.');
        }
    }
 }
