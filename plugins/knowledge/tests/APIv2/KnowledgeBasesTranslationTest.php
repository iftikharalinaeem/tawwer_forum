<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Test the /api/v2/knowledge-bases endpoint.
 */
class KnowledgeBasesTransaltionTest extends AbstractAPIv2Test {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /** @var string $translationApi Translation api path */
    protected $translationApi = "/translations";

    protected static $addons = ['vanilla', 'sphinx', 'knowledge', 'translationsapi'];

    protected static $enabledLocales = ['vf_fr' => 'fr', 'vf_es' => 'es', 'vf_ru' => 'ru'];

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        'name',
        'description',
        'viewType',
        'icon',
        'sortArticles',
        'sourceLocale',
        'urlCode',
        'siteSectionGroup'
    ];

    /**
     * Generate a unique URL code for a knowledge base
     *
     * @return string
     */
    public static function getUniqueUrlCode(): string {
        static $inc = 0;
        return 'kb-url-code' . $inc++;
    }

    /**
     * Grab values for inserting a new knowledge base.
     *
     * @param string $name Name of the knowledge base.
     * @return array
     */
    public function record(string $name = 'Test Knowledge Base'): array {
        static $knowledgeBaseID = 1;

        $record = [
            'name' => $name,
            'description' => 'Test Knowledge Base DESCRIPTION',
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => self::getUniqueUrlCode(),
            'siteSectionGroup' => 'mockSiteSectionGroup-1'
        ];
        return $record;
    }

    /**
     *
     * @param array $record
     * @param array $properties
     * @param string $locale
     * @return \Garden\Http\HttpResponse
     */
    private function generateTranslation(array $record, array $properties, string $locale) {
        $patchBody = [];
        foreach ($properties as $propertyName) {
            $patchBody[] = [
                'recordType' => KnowledgeBaseModel::RECORD_TYPE,
                'recordID' => $record[KnowledgeBaseModel::RECORD_ID],
                'propertyName' => $propertyName,
                'locale' => $locale,
                'translation' => $record[$propertyName].' - '.$locale
            ];
        }
        $result = $this->api()->put($this->translationApi.'/kb', $patchBody);
        return $result;
    }


    /**
     * Prepare few knowledge bases and translated content
     */
    public function testPrepareTranslations() {
        $result = $this->api()->post(
            $this->translationApi,
            [
                'name' => "knowledgebase",
                'urlCode' => 'kb',
                'sourceLocale' => 'en'
            ]
        );
        $this->assertEquals(201, $result->getStatusCode());
        for ($i = 1; $i<4; $i++) {
            $kb = $this->record('ORIGINAL ('.$i.')');
            $result = $this->api()->post(
                $this->baseUrl,
                $kb
            );
            $kb = $this->api()->get(
                $this->baseUrl.'/'.$i,
                $kb
            )->getBody();
            switch ($i) {
                case 1:
                    $result = $this->generateTranslation($kb, ['name', 'description'], 'fr');
                    break;
                case 2:
                    $result = $this->generateTranslation($kb, ['name', 'description'], 'es');
                    break;
                default:
                    //just do no translation
                    ;
            }
        }
    }

    /**
     *
     * @param int $id
     * @param string $locale
     * @depends testPrepareTranslations
     * @dataProvider translationsdDataProvider
     */
    public function testKnowledgeBaseLocales (int $id, string $locale) {
        $allKbs = $this->api()->get($this->baseUrl.'?locale='.$locale)
            ->getBody();
        $this->assertEquals(3, count($allKbs));

        for ($i = 1; $i<3; $i++) {
            $result = $this->api()->get($this->baseUrl.'/'.$i.'?locale='.$locale)->getBody();
            if ($i === $id) {
                $this->assertStringEndsWith(' - '.$locale, $result['name']);
                $this->assertStringEndsWith(' - '.$locale, $result['description']);
            } else {
                $this->assertStringEndsNotWith(' - '.$locale, $result['name']);
                $this->assertStringEndsNotWith(' - '.$locale, $result['description']);
            }
        }
    }

    /**
     * Test cases data provider
     */
    public function translationsdDataProvider() {
        return [
            'French' => [1, 'fr'],
            'Spanish' => [2, 'es'],
        ];
    }
}
