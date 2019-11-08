<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\TranslationsAPI\models\TranslationModel;
use Vanilla\TranslationsAPI\models\TranslationPropertyModel;


/**
 * Test Translations APIv2 endpoints.
 */
class TranslationsTests extends AbstractAPIv2Test {
    /**
     * {@inheritdoc}
     */
    public function setup() {
        parent::setUp();
    }

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass() {
        self::$addons = ['translationsApi', 'vanilla'];
        parent::setUpBeforeClass();
    }

    /**
     * Test Post /translations
     */
    public function testPostResource() {
        $resource = [
            "name" => "knowledgebase",
            "sourceLocale" => "en",
            "urlCode" => "kb",
        ];

        $result = $this->api()->post(
            'translations',
            $resource
        );
        $this->assertEquals(201, $result->getStatusCode());
    }

    /**
     *  Post /translations failure
     *
     * @depends testPostResource
     */
    public function testPostResourceFailure() {
        $this->expectException(ClientException::class);
        $resource = [
            "name" => "knowledgebase",
            "sourceLocale" => "en",
            "urlCode" => "kb",
        ];

        $this->api()->post(
            'translations',
            $resource
        );
    }
}
