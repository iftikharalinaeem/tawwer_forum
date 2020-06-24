<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Fixtures\KbPageFixture;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SingleSiteSectionProvider;
use Vanilla\Site\SiteSectionModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\fixtures\MockSiteSection;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Tests for the KbPage class.
 */
class KbPageTest extends AbstractAPIv2Test {

    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

    /**
     * Test that the render function requires site section validation in debug mode.
     */
    public function testRequiredSectionValidation() {

        // No error in debug mode.
        /** @var KbPageFixture $page */
        $page = self::container()->get(KbPageFixture::class);
        $page->initialize();
        $page->render(); // Should work correctly.

        // Check in debug mode.
        saveToConfig('Debug', true, false);
        /** @var KbPageFixture $page */
        $page = self::container()->get(KbPageFixture::class);
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("Site Section must be validated");
        $page->render();
    }

    /**
     * Test validation of site section groups.
     */
    public function testGroupValidation() {
        $router = self::container()->get(\Gdn_Router::class);
        $defaultSection = new DefaultSiteSection(new MockConfig(), $router);
        /** @var SiteSectionProviderInterface $siteSectionProvider */
        $siteSectionProvider = self::container()->get(MockSiteSectionProvider::class);

        $section1 = new MockSiteSection(
            "siteSectionName_en",
            'en',
            '/en',
            "mockSiteSection-en",
            "mockSiteSectionGroup-1",
            [
                'Destination' => 'discussions',
                'Type' => 'Internal'
            ],
            'keystone'
        );
        $section2 = new MockSiteSection(
            "ssg2_siteSectionName_en",
            'en',
            '/ssg2-en',
            "ssg2-mockSiteSection-en",
            "mockSiteSectionGroup-2",
            [
                'Destination' => 'discussions',
                'Type' => 'Internal'
            ],
            'keystone'
        );

        // Insert a knowledge base in $section1's group.
        $kb = [
            'name' => 'Test Knowledge Base',
            'description' => 'Test Knowledge Base',
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => 'test-knowledge-base-kb-page',
            "siteSectionGroup" => "mockSiteSectionGroup-1",
        ];
        $response = $this->api()->post('/knowledge-bases', $kb);
        $kbID = $response['knowledgeBaseID'];


        /** @var KbPageFixture $page */
        $page = self::container()->get(KbPageFixture::class);
        // Passthrough in correct section.
        $siteSectionProvider->setCurrentSiteSection($section1);
        $page->validateSiteSection($kbID);

        // Passthrough in default section.
        $siteSectionProvider->setCurrentSiteSection($defaultSection);
        $page->validateSiteSection($kbID);

        // Throw an error if the site section is incorrect
        $this->expectException(NotFoundException::class);
        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel = self::container()->get(SiteSectionModel::class);
        $newSiteSectionProvider = new MockSiteSectionProvider($defaultSection);
        $newSiteSectionProvider->addSiteSections([$section2]);
        $newSiteSectionProvider->setCurrentSiteSection($section2);
        $siteSectionModel->addProvider($newSiteSectionProvider);
        $page->validateSiteSection($kbID);
    }

    /**
     * Test page number parsing.
     *
     * @param string $path
     * @param int $expectedNumber
     *
     * @dataProvider providePageNumbers
     */
    public function testPageNumber(string $path, int $expectedNumber) {
        /** @var KbPageFixture $page */
        $page = self::container()->get(KbPageFixture::class);

        $this->assertEquals($expectedNumber, $page->parsePageNumberFromPath($path, $expectedNumber));
    }

    /**
     * @return array
     */
    public function providePageNumbers(): array {
        return [
            ['/test/test/path/p642/p12', 12],
            ['/test/p6', 6],
            ['asdf', 1],
            ['asdf$#%$^%&*&^%$#!/', 1]
        ];
    }
}
