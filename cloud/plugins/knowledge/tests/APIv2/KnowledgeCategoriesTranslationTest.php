<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Test the /api/v2/knowledge-categories GET endpoints with locale param and translationApi enabled
 */
class KnowledgeCategoriesTranslationTest extends KbApiTestCase {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string $translationApi Translation api path */
    protected $translationApi = "/translations";

    protected static $addons = ['vanilla', 'translationsapi', 'sphinx', 'knowledge'];

    protected static $enabledLocales = ['vf_fr' => 'fr', 'vf_es' => 'es', 'vf_ru' => 'ru'];

    /**
     * Grab values for inserting a new knowledge base.
     *
     * @param string $name Name of the knowledge base.
     * @return array
     */
    public function kbRecord(string $name = 'Test Knowledge Base'): array {
        static $knowledgeBaseID = 1;

        $record = [
            'name' => $name,
            'description' => $name.' DESCRIPTION',
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => 'test-kb',
            'siteSectionGroup' => 'mockSiteSectionGroup-1'
        ];
        return $record;
    }

    /**
     * Grab values for inserting a new knowledge category.
     *
     * @param array $record Record defaults
     *
     * @return array
     */
    public function kbCategoryRecord(array $record = []): array {
        $record = [
            "name" => $record['name'] ?? "Test Knowledge Category",
            "parentID" => $record['parentID'] ?? -1,
            "knowledgeBaseID" => $record['knowledgeBaseID'] ?? 1,
            "sortChildren" => $record['sortChildren'] ?? "name",
            "sort" => $record['sort'] ?? 0,
        ];
        return $record;
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
                'recordType' => KnowledgeCategoryModel::RECORD_TYPE,
                'recordID' => $record[KnowledgeCategoryModel::RECORD_ID_FIELD],
                'propertyName' => $propertyName,
                'locale' => $locale,
                'translation' => $record[$propertyName].' - '.$locale
            ];
        }
        $result = $this->api()->patch($this->translationApi.'/kb', $patchBody);
        return $result;
    }


    /**
     * Prepare few knowledge bases and translated content.
     *
     * Generate 2 translated knowledge bases, and 2 untranslated knowledge bases.
     */
    public function testPrepareTranslations() {
        $this->assertTrue(true); // Need because an assertion is required
        $kb = $this->kbRecord('ORIGINAL KB');
        $result = $this->api()->post('/knowledge-bases', $kb);
        $kb = $this->api()->get('/knowledge-bases/1', $kb)
            ->getBody();
        for ($i = 1; $i<4; $i++) {
            $kbCategory = $this->kbCategoryRecord();
            //$kbCategory['parentID'] = $kb['rootCategoryID'];
            $kbCategory = $this->api()->post($this->baseUrl, $kbCategory)->getBody();
            switch ($i) {
                case 1:
                    $result = $this->generateTranslation($kbCategory, ['name'], 'fr');
                    break;
                case 2:
                    $result = $this->generateTranslation($kbCategory, ['name'], 'es');
                    break;
                default:
                    //just do no translation
            }
        }
    }

    /**
     * Test GET /api/v2/knowledge-categories/{id}
     *
     * @param int $id
     * @param string $locale
     * @depends testPrepareTranslations
     * @dataProvider translationsDataProvider
     */
    public function testKnowledgeCategoryIdLocales(int $id, string $locale) {
        for ($i = 1; $i<5; $i++) {
            $result = $this->api()->get($this->baseUrl.'/'.$i.'?locale='.$locale)->getBody();
            if ($i === $id) {
                $this->assertStringEndsWith(' - '.$locale, $result['name']);
                $this->assertStringEndsWith('category-'.$locale, $result['url']);
            } else {
                $this->assertStringEndsNotWith(' - '.$locale, $result['name']);
                $this->assertStringEndsNotWith('category-'.$locale, $result['url']);
            }
        }
    }

    /**
     * Test GET /api/v2/knowledge-categories (index)
     *
     * @param int $id
     * @param string $locale
     * @depends testPrepareTranslations
     * @dataProvider translationsDataProvider
     */
    public function testKnowledgeCategoryIndexLocales(int $id, string $locale) {
        $allCategories = $this->api()->get($this->baseUrl.'?locale='.$locale)
            ->getBody();
        $this->assertEquals(4, count($allCategories));

        foreach ($allCategories as $category) {
            if ($category[KnowledgeCategoryModel::RECORD_ID_FIELD] === $id) {
                $this->assertStringEndsWith(' - '.$locale, $category['name']);
                $this->assertStringEndsWith('category-'.$locale, $category['url']);
            } else {
                $this->assertStringEndsNotWith(' - '.$locale, $category['name']);
                $this->assertStringEndsNotWith('category-'.$locale, $category['url']);
            }
        }
    }

    /**
     * Test cases data provider
     */
    public function translationsDataProvider() {
        return [
            'French' => [2, 'fr'],
            'Spanish' => [3, 'es'],
        ];
    }
}
