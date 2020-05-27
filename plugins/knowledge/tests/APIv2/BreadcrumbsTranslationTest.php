<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Test GET /api/v2/knowledge-categories endpoint to return correct breadcrumbs.
 */
class BreadcrumbsTranslationTest extends KbApiTestCase {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string $translationApi Translation api path */
    protected $translationApi = "/translations";

    private static $preparedCategoryData = [];

    protected static $addons = ['vanilla', 'translationsapi', 'sphinx', 'knowledge'];

    protected static $enabledLocales = ['vf_fr' => 'fr', 'vf_es' => 'es', 'vf_ru' => 'ru'];

    /**
     * Generate kb and few categories.
     */
    public function testPrepareCategoryData() {
        $this->assertTrue(true); // Need because an assertion is required

        $knowledgeBase = $this->api()->post('knowledge-bases', [
            "name" => __FUNCTION__ . " Test Knowledge Base",
            "description" => 'Some description',
            "urlCode" => slugify('test-Knowledge-Base'),
            "viewType" => KnowledgeBaseModel::TYPE_HELP,
            "sortArticles" => KnowledgeBaseModel::ORDER_NAME,
            "siteSectionGroup" => 'mockSiteSectionGroup-1'
        ])->getBody();
        $knowledgeBase['RECORD_TYPE'] = KnowledgeBaseModel::RECORD_TYPE;
        $knowledgeBase['RECORD_ID_FIELD'] = KnowledgeBaseModel::RECORD_ID_FIELD;
        foreach (self::$enabledLocales as $localeKey => $locale) {
            $this->generateTranslation($knowledgeBase, ['name', 'description'], $locale);
        }

        // Setup the test categories.
        $rootCategory = $this->api()->get($this->baseUrl.'/'.$knowledgeBase['rootCategoryID'])->getBody();

        $subCat1 = $this->api()->post($this->baseUrl, [
            "parentID" => $rootCategory["knowledgeCategoryID"],
            "name" => "LEVEL 1",
        ])->getBody();
        $subCat1['RECORD_TYPE'] = KnowledgeCategoryModel::RECORD_TYPE;
        $subCat1['RECORD_ID_FIELD'] = KnowledgeCategoryModel::RECORD_ID_FIELD;
        $this->generateTranslation($subCat1, ['name'], 'fr');

        $subCat2 = $this->api()->post($this->baseUrl, [
            "parentID" => $rootCategory["knowledgeCategoryID"],
            "name" => "LEVEL 2",
        ])->getBody();
        $subCat2['RECORD_TYPE'] = KnowledgeCategoryModel::RECORD_TYPE;
        $subCat2['RECORD_ID_FIELD'] = KnowledgeCategoryModel::RECORD_ID_FIELD;
        $this->generateTranslation($subCat2, ['name'], 'es');

        $subCat3 = $this->api()->post($this->baseUrl, [
            "parentID" => $rootCategory["knowledgeCategoryID"],
            "name" => "LEVEL 3",
        ])->getBody();

        self::$preparedCategoryData = [
            'knowledgeBase' => $knowledgeBase,
            'rootCategory' => $rootCategory,
            'subCat1' => $subCat1,
            'subCat2' => $subCat2,
            'subCat3' => $subCat3,
        ];

        $r = $this->api()->get($this->baseUrl)->getBody();

        $this->assertEquals(4, count($r));
    }

    /**
     * Test breadcrumbs reflect to locale param when GET knowledge-categories api.
     *
     * @param string $knowledgeCategoryKey
     * @param string $locale
     *
     * @depends testPrepareCategoryData
     * @dataProvider translationsDataProvider
     */
    public function testBreadcrumbsTranslated(string $knowledgeCategoryKey, string $locale) {
        $kb = self::$preparedCategoryData['knowledgeBase'];
        $cat = self::$preparedCategoryData[$knowledgeCategoryKey];

        $r = $this->api()->get(
            $this->baseUrl.'/'.$cat['knowledgeCategoryID'].'?locale='.$locale
        )->getBody();
        $this->assertArrayHasKey('breadcrumbs', $r);
        $breadcrumbs = $r['breadcrumbs'];
        foreach ($breadcrumbs as $breadcrumb) {
            $url = $breadcrumb['url'];
            $name = $breadcrumb['name'];
            // check that site section url slug applied correctly
            $this->assertGreaterThan(1, strpos($url, '/'.$locale.'/kb/'));
            if ($locale !== $kb['sourceLocale'] &&
                ((strpos($url, '/kb/categories/'.$r[KnowledgeCategoryModel::RECORD_ID_FIELD].'-') > 0)
                || strpos($url, '/kb/categories/') === false)) {
                $this->assertStringEndsWith(' - '.$locale, $name);
                if (strpos($url, '/kb/categories/') !== false) {
                    //translated knowledge category url should reflect to translated name
                    $this->assertStringEndsWith('-'.$locale, $url);
                } else {
                    //translated knowledge base url should not reflect to translated name
                    $this->assertStringEndsNotWith('-'.$locale, $url);
                }
            } else {
                // when category wasn't translated or locale = sourceLocale
                $this->assertStringEndsNotWith(' - ' . $locale, $name);
                $this->assertStringEndsNotWith('-' . $locale, $url);
            }
        }
    }

    /**
     * Test cases data provider
     */
    public function translationsDataProvider() {
        return [
            'Level 1' => ['subCat1', 'fr'],
            'Level 2' => ['subCat2', 'es'],
            'Level 3' => ['subCat3', 'en']
        ];
    }

    /**
     * Generate some content
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
                'recordType' => $record['RECORD_TYPE'],
                'recordID' => $record[$record['RECORD_ID_FIELD']],
                'propertyName' => $propertyName,
                'locale' => $locale,
                'translation' => $record[$propertyName].' - '.$locale
            ];
        }
        $result = $this->api()->patch($this->translationApi.'/kb', $patchBody);
        return $result;
    }
}
