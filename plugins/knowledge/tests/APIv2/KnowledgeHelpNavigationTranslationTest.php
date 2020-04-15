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
 * Test GET /api/v2/knowledge-bases/{id}/navigation-flat endpoint with locale param and translationApi enabled
 */
class KnowledgeHelpNavigationTranslationTest extends KbApiTestCase {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string $translationApi Translation api path */
    protected $translationApi = "/translations";

    /** @var string $navigationApi Knowledge navigation api path */
    protected $navigationApi = "/knowledge-bases/{id}/navigation-flat";

    protected static $addons = ['vanilla', 'translationsapi', 'sphinx', 'knowledge'];

    protected static $enabledLocales = ['vf_fr' => 'fr', 'vf_es' => 'es', 'vf_ru' => 'ru'];

    private static $content = [];

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
                'translation' => '0 '.$record[$propertyName].' - '.$locale
            ];
        }
        $result = $this->api()->patch($this->translationApi.'/kb', $patchBody);
        return $result;
    }

    /**
     * Generate some content
     *
     * @param array $article
     * @param string $locale
     * @return \Garden\Http\HttpResponse
     */
    private function generateArticleTranslation(array $article, string $locale) {
        $articleLocale['locale'] = $locale;
        $articleLocale['name'] = '0 '.$locale.' '.$article['name'];
        $articleLocale['body'] = '0 '.$locale.' '.$article['body'];
        $articleLocale['format'] = 'text';

        $result = $this->api()->patch('/articles/'.$article['articleID'], $articleLocale);
        return $result;
    }


    /**
     * Prepare few knowledge bases and translated content.
     *
     * Generate 2 translated knowledge bases, and 2 untranslated knowledge bases.
     */
    public function testPrepareGuideKbTranslations() {
        $this->assertTrue(true); // Need because an assertion is required
        $helpKb = [
            'name' => 'ORIGINAL GUIDE KB',
            'description' => 'ORIGINAL GUIDE KB DESCRIPTION',
            'viewType' => KnowledgeBaseModel::TYPE_HELP,
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => KnowledgeBaseModel::ORDER_NAME,
            'sourceLocale' => 'en',
            'urlCode' => 'test-kb',
            'siteSectionGroup' => 'mockSiteSectionGroup-1'
        ];
        $result = $this->api()->post('/knowledge-bases', $helpKb)->getBody();
        $helpKb = $this->api()->get('/knowledge-bases/'.$result['knowledgeBaseID'], $helpKb)
            ->getBody();
        self::$content['kbHelp'] = $helpKb;
        self::$content['cat0'] = $cat0 = $this->api()->get($this->baseUrl.'/'.$helpKb['rootCategoryID'])
            ->getBody();

        for ($i = 1; $i<5; $i++) {
            $kbCategory = $this->kbCategoryRecord();
            $kbCategory['name'] = $i.' '.$kbCategory['name'];
            $kbCategory = $this->api()->post($this->baseUrl, $kbCategory)->getBody();

            self::$content['article'.$i] = $article = $this->api()->post('/articles', [
                "knowledgeCategoryID" => $cat0["knowledgeCategoryID"],
                "name" => $i." Article",
                "body" => 'Text',
                "format" => "text",
            ])->getBody();

            self::$content['cat'.$i] = $kbCategory;
            switch ($i) {
                case 2:
                    $result = $this->generateTranslation($kbCategory, ['name'], 'fr');
                    $result = $this->generateArticleTranslation($article, 'fr');
                    break;
                case 3:
                    $result = $this->generateTranslation($kbCategory, ['name'], 'es');
                    $result = $this->generateArticleTranslation($article, 'es');
                    break;
                default:
                    //just do no translation
            }
        }
    }

    /**
     * Test GET /api/v2/knowledge-bases/{id}/navigation-flat?locale={locale}
     *
     * @param string $locale
     * @param string $kbKey
     * @param array $order
     * @depends testPrepareGuideKbTranslations
     * @dataProvider navigationKbGuideProvider
     */
    public function testGuideNavigationTranslation(string $locale, string $kbKey, array $order) {
        $kb = self::$content[$kbKey];
        $path = str_replace('{id}', $kb[KnowledgeBaseModel::RECORD_ID_FIELD], $this->navigationApi);
        $navigation = $this->api()->get($path.'?locale='.$locale)
            ->getBody();
        $this->assertEquals(count($order), count($navigation));
        foreach ($navigation as $index => $item) {
            $this->assertStringEndsWith($order[$index]['name'], $item['name']);
            if ($item['recordType'] === 'knowledgeCategory') {
                $regExp = '/'.$locale.'\/kb\/categories\/'.self::$content[$order[$index]['key']]['knowledgeCategoryID'].'-/';
            } elseif ($item['recordType'] === 'article') {
                $regExp = '/'.$locale.'\/kb\/articles\/'.self::$content[$order[$index]['key']]['articleID'].'-/';
            }
            $this->assertRegexp($regExp, $item['url']);
        }
    }

    /**
     * Test cases data provider
     */
    public function navigationKbGuideProvider() {
        return [
            'GUIDE KB' => ['en', 'kbHelp', [
                ['name' => 'GUIDE KB', 'key' => 'cat0'],
                ['name' => 'Category', 'key' => 'cat1'],
                ['name' => 'Category', 'key' => 'cat2'],
                ['name' => 'Category', 'key' => 'cat3'],
                ['name' => 'Category', 'key' => 'cat4'],
                ['name' => '1 Article', 'key' => 'article1'],
                ['name' => '2 Article', 'key' => 'article2'],
                ['name' => '3 Article', 'key' => 'article3'],
                ['name' => '4 Article', 'key' => 'article4'],
            ]],
            'GUIDE KB FR' => ['fr', 'kbHelp', [
                ['name' => 'GUIDE KB', 'key' => 'cat0'],
                ['name' => 'Category', 'key' => 'cat1'],
                ['name' => 'Category - fr', 'key' => 'cat2'],
                ['name' => 'Category', 'key' => 'cat3'],
                ['name' => 'Category', 'key' => 'cat4'],
                ['name' => '0 fr 2 Article', 'key' => 'article2'],
                ['name' => '1 Article', 'key' => 'article1'],
                ['name' => '3 Article', 'key' => 'article3'],
                ['name' => '4 Article', 'key' => 'article4'],
            ]],
            'GUIDE KB ES' => ['es', 'kbHelp', [
                ['name' => 'GUIDE KB', 'key' => 'cat0'],
                ['name' => 'Category', 'key' => 'cat1'],
                ['name' => 'Category', 'key' => 'cat2'],
                ['name' => 'Category - es', 'key' => 'cat3'],
                ['name' => 'Category', 'key' => 'cat4'],
                ['name' => '0 es 3 Article', 'key' => 'article3'],
                ['name' => '1 Article', 'key' => 'article1'],
                ['name' => '2 Article', 'key' => 'article2'],
                ['name' => '4 Article', 'key' => 'article4'],
            ]],
        ];
    }
}
