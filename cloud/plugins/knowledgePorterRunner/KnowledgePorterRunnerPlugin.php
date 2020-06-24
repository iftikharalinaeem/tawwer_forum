<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class KnowledgePorterRunnerPlugin
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class KnowledgePorterRunnerPlugin extends Gdn_Plugin {
    /** @var ConfigurationInterface */
    protected $config;

    /**
     * KnowledgePorterRunnerPlugin constructor.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        parent::__construct();

        $this->config = $config;
    }

    /**
     * Setup routine for the addon.
     *
     * @return bool|void
     * @throws Exception For parent exceptions.
     */
    public function setup() {
        parent::setup();

        if ($this->config->get('Plugins.KnowledgePorterRunner.token', false) === false) {
            $this->config->set('Plugins.KnowledgePorterRunner.token', $this->generateToken(32));
        }

        return true;
    }

    /**
     * Generate a random token
     * Thanks to https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string
     *
     * @vf-improve: Dig into vanilla/vanilla code and find a similar (already existing) function. Ex: user password generator
     *
     * @param integer $length
     * @return string
     * @throws Exception If it was not possible to gather sufficient entropy.
     */
    protected function generateToken(int $length) {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $max = strlen($codeAlphabet);

        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[random_int(0, $max - 1)];
        }

        return $token;
    }
}
