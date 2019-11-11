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
            "name" => "resource one name",
            "sourceLocale" => "en",
            "urlCode" => "resourceOne",
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
            "name" => "resource one name",
            "sourceLocale" => "en",
            "urlCode" => "resourceOne",
        ];

        $this->api()->post(
            'translations',
            $resource
        );
    }

    /**
     * Post /translations failure
     *
     * @param array $record
     * @param string $key
     * @param string $translation
     *
     * @depends      testPostResource
     * @dataProvider translationsPropertyProvider
     */
    public function testPutTranslations($record, $key, $translation) {
        $this->api()->put(
            'translations/resourceOne',
            $record
        );

        $record = reset($record);
        $translationPropertyModel = self::container()->get(TranslationPropertyModel::class);
        $result = $translationPropertyModel->get(["recordType" => $record["recordType"], "recordID" => $record["recordID"], "propertyName" => $record["propertyName"]]);
        $translationModel = self::container()->get(TranslationModel::class);
        $this->assertEquals($key, $result[0]["translationPropertyKey"]);

        $result = $translationModel->get(["translationPropertyKey" => $result[0]["translationPropertyKey"]]);
        $this->assertEquals($translation, $result[0]["translation"]);
    }

    /**
     * Provider for PUT /translations/:resource
     *
     * @return array
     */
    public function translationsPropertyProvider(): array {
        return [
            [
                [[
                    "recordType" => "recordTypeOne",
                    "recordID" => 8,
                    "recordKey" => "",
                    "propertyName" => "name",
                    "locale" => "en",
                    "translation" => "english recordTypeOne name"
                ]],
                "recordTypeOne.8.name",
                "english recordTypeOne name",
            ],
            [
                [[
                    "recordType" => "recordTypeTwo",
                    "recordID" => 9,
                    "recordKey" => "",
                    "propertyName" => "description",
                    "locale" => "en",
                    "translation" => "english recordTypeTwo cat description"
                ]],
                "recordTypeTwo.9.description",
                "english recordTypeTwo cat description",
            ],
            [
                [[
                    "recordType" => "recordTypeThree",
                    "recordID" => null,
                    "recordKey" => null,
                    "propertyName" => "name",
                    "locale" => "en",
                    "translation"=> "name"
                ]],
                "recordTypeThree..name",
                "name",
            ],
        ];
    }
}
