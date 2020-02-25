<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeUniversalSourceModel;

/**
 * Test the /api/v2/articles endpoint.
 */
class KnowledgeBasesUniversalContentTest extends AbstractAPIv2Test {

    /** @var array */
    private static $targetKnowledgeBaseIDs;

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Provide knowledge-base record.
     *
     * @param bool $isUniversalSource
     * @param array $targetIDs
     * @return array
     */
    public function knowledgeBaseRecord(bool $isUniversalSource = false, array $targetIDs = []) {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        return $record = [
            'name' => 'Test Knowledge Base',
            'description' => 'Test Knowledge Base ' . $salt,
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => 'test-knowledge-base' . $salt,
            "siteSectionGroup" => "vanilla",
            "isUniversalSource" => $isUniversalSource,
            "universalTargetIDs" => $targetIDs
        ];
    }

    /**
     * Create new knowledge base.
     *
     * @return array Knowledge base
     */
    public function createKnowledgeBases(): array {
        $kbs = [];
        for ($i = 0; $i <= 6; $i++) {
            $kb = $this->api()
                ->post($this->baseUrl, $this->knowledgeBaseRecord())
                ->getBody();

            $kbs[] = $kb["knowledgeBaseID"];
        }
        return $kbs;
    }

    /**
     * Prepare knowledge base data for tests and reindex Sphinx indexes.
     */
    public function testData() {
        self::$targetKnowledgeBaseIDs = $this->createKnowledgeBases();
        $this->assertTrue(true);
    }

    /**
     * POST /knowledge-bases with isUniversal true.
     *
     * @param bool $isUniversal
     * @param array $targetIDs
     *
     * @return array
     */
    public function createKnowledgeBaseWithUniversalContent(bool $isUniversal = true, array $targetIDs = []) {
        $body = $this->knowledgeBaseRecord($isUniversal, $targetIDs);
        $kb = $this->api()
            ->post($this->baseUrl, $body)
            ->getBody();

        return $kb;
    }


    /**
     * Test POST /knowledge-bases with isUniversal true.
     *
     * @depends testData
     */
    public function testPostIsUniversalTrueWithTargets() {

        $kb = $this->createKnowledgeBaseWithUniversalContent(true, self::$targetKnowledgeBaseIDs);

        $this->assertEquals(true, $kb["isUniversalSource"]);
        $this->assertEquals(self::$targetKnowledgeBaseIDs, $kb["universalTargetIDs"]);
    }

    /**
     * Test POST /knowledge-bases with invalid isUniversal status.
     *
     * @depends testData
     */
    public function testPostWithInvalidStatus() {
        $this->expectException(ClientException::class);

        $kb = $this->createKnowledgeBaseWithUniversalContent(false, self::$targetKnowledgeBaseIDs);

        $this->assertEquals(true, $kb["isUniversalSource"]);
        $this->assertEquals(self::$targetKnowledgeBaseIDs, $kb["universalTargetIDs"]);
    }

    /**
     * Test POST /knowledge-bases with invalid target kb.
     *
     * @depends testData
     */
    public function testPostWithInvalidTarget() {
        $this->expectException(ClientException::class);
        $this->setKBIsUniversal(true, self::$targetKnowledgeBaseIDs[0]);

        $kb = $this->createKnowledgeBaseWithUniversalContent(true, self::$targetKnowledgeBaseIDs);

        $this->assertEquals(true, $kb["isUniversalSource"]);
        $this->assertEquals(self::$targetKnowledgeBaseIDs, $kb["universalTargetIDs"]);
    }

    /**
     * Test PATCH /knowledge-bases with isUniversal true.
     */
    public function testPatchWithUniversalTrueNoTargets() {
        $kb = $this->createKnowledgeBaseWithUniversalContent(false);
        $response = $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], ["isUniversalSource" => true])
            ->getBody();

        $this->assertEquals(true, $response["isUniversalSource"]);
    }

    /**
     * Test PATCH /knowledge-bases with isUniversal true and target kb ids.
     *
     * @depends testData
     */
    public function testPatchWithUniversalTrueWithTargets() {
        $kb = $this->createKnowledgeBaseWithUniversalContent(false);

        $response = $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "isUniversalSource" => true,
                "universalTargetIDs" => [
                    self::$targetKnowledgeBaseIDs[1],
                    self::$targetKnowledgeBaseIDs[2]
                ]
            ])
            ->getBody();

        $this->assertEquals(true, $response["isUniversalSource"]);

        return $response;
    }

    /**
     * Test PATCH /knowledge-bases with isUniversal is true and check if
     * knowledgeUniversalSource records have been removed.
     *
     * @depends testData
     */
    public function testPatchUniversalFalseWithCleanUp() {
        $kb = $this->testPatchWithUniversalTrueWithTargets();
        $response = $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "isUniversalSource" => false,
            ])
            ->getBody();
        $this->assertEquals(false, $response["isUniversalSource"]);

        /** @var KnowledgeUniversalSourceModel $KnowledgeUniversalSourceModel */
        $KnowledgeUniversalSourceModel = self::container()->get(KnowledgeUniversalSourceModel::class);
        $records = $KnowledgeUniversalSourceModel->get(["sourceKnowledgeBaseID" => $kb["knowledgeBaseID"]]);

        $this->assertEquals(0, count($records));
    }

    /**
     * Test PATCH /knowledge-bases with invalid target kb.
     *
     * @depends testData
     */
    public function testPatchWithInvalidTarget() {
        $this->expectException(ClientException::class);
        $kb = $this->createKnowledgeBaseWithUniversalContent();

        $response = $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "universalTargetIDs" => [
                    self::$targetKnowledgeBaseIDs[0],
                    self::$targetKnowledgeBaseIDs[1]
                ]
            ])
            ->getBody();

        $this->assertEquals(true, $response["isUniversalSource"]);
    }

    /**
     * Test PATCH /knowledge-bases with isUniversal status.
     *
     * @depends testData
     */
    public function testPatchWithInvalidStatus() {
        $this->expectException(ClientException::class);
        $kb = $this->createKnowledgeBaseWithUniversalContent();

        $response = $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "isUniversalSource" => false,
                "universalTargetIDs" => [
                    self::$targetKnowledgeBaseIDs[1],
                    self::$targetKnowledgeBaseIDs[2]
                ]
            ])
            ->getBody();

        $this->assertEquals(true, $response["isUniversalSource"]);
    }

    /**
     * Test PATCH /knowledge-bases with just target kbs.
     *
     * @depends testData
     */
    public function testPatchWithJustTargets() {
        $kb = $this->createKnowledgeBaseWithUniversalContent(true);

        $response = $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "universalTargetIDs" => [
                    self::$targetKnowledgeBaseIDs[1],
                    self::$targetKnowledgeBaseIDs[2]
                ]
            ])
            ->getBody();

        $this->assertEquals(true, $response["isUniversalSource"]);
    }

    /**
     * Test PATCH /knowledge-bases with just target kbs and invalid isUniversalSource
     * status.
     *
     * @depends testData
     */
    public function testPatchWithJustTargetsFail() {
        $this->expectException(ClientException::class);
        $kb = $this->createKnowledgeBaseWithUniversalContent();
        $this->setKBIsUniversal(false, $kb["knowledgeBaseID"]);

        $response = $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "universalTargetIDs" => [
                    self::$targetKnowledgeBaseIDs[1],
                    self::$targetKnowledgeBaseIDs[2]
                ]
            ])
            ->getBody();

        $this->assertEquals(true, $response["isUniversalSource"]);
    }

    /**
     * Test GET /knowledge-bases with expand universalTargets param.
     *
     * @depends testData
     */
    public function testGetUniversalKBWithTargets() {
        $kb = $this->createKnowledgeBaseWithUniversalContent();
        $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "universalTargetIDs" => [
                    self::$targetKnowledgeBaseIDs[1],
                    self::$targetKnowledgeBaseIDs[2],
                    self::$targetKnowledgeBaseIDs[3]
                ]
            ])
            ->getBody();

        $response = $this->api()
            ->get($this->baseUrl.'/'.$kb["knowledgeBaseID"], ["expand" => "universalTargets"])
            ->getBody();

        $this->assertEquals($kb["knowledgeBaseID"], $response["knowledgeBaseID"]);
        $this->assertEquals(
            [
                self::$targetKnowledgeBaseIDs[1],
                self::$targetKnowledgeBaseIDs[2],
                self::$targetKnowledgeBaseIDs[3]
            ],
            $response["universalTargetIDs"]
        );
        $this->assertEquals(3, count($response["universalTargets"]));
    }

    /**
     * Test GET /knowledge-bases with expand universalSources param.
     *
     * @depends testData
     */
    public function testGetUniversalKBWithSources() {
        /** @var KnowledgeUniversalSourceModel $KnowledgeUniversalSourceModel */
        $KnowledgeUniversalSourceModel = self::container()->get(KnowledgeUniversalSourceModel::class);
        $KnowledgeUniversalSourceModel->delete(["targetKnowledgeBaseID" =>  self::$targetKnowledgeBaseIDs[5]]);

        $kb = $this->createKnowledgeBaseWithUniversalContent();
        $this->api()
            ->patch($this->baseUrl.'/'.$kb["knowledgeBaseID"], [
                "universalTargetIDs" => [
                    self::$targetKnowledgeBaseIDs[5]
                ]
            ])
            ->getBody();

        $response = $this->api()
            ->get($this->baseUrl.'/'. self::$targetKnowledgeBaseIDs[5], ["expand" => "universalSources"])
            ->getBody();

        $this->assertEquals(self::$targetKnowledgeBaseIDs[5], $response["knowledgeBaseID"]);
        $this->assertEquals([$kb["knowledgeBaseID"]], $response["universalSourceIDs"]);
        $this->assertEquals(1, count($response["universalSources"]));
    }

    /**
     * Set a knowledge-bases isUniversal status.
     *
     * @param bool $isUniversal
     * @param int $id
     */
    protected function setKBIsUniversal(bool $isUniversal, int $id): void {
        $isUniversal = $isUniversal ? 1 : 0;

        /** @var KnowledgeBaseModel $knowledgeBaseModel */
        $knowledgeBaseModel = self::container()->get(KnowledgeBaseModel::class);
        $knowledgeBaseModel->update(["isUniversalSource" => $isUniversal], ["knowledgeBaseID" => $id]);
    }
}
