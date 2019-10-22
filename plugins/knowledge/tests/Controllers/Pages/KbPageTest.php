<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\Controllers\Pages;

use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Fixtures\KbPageFixture;
use Vanilla\Site\DefaultSiteSection;
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
        $defaultSection = new DefaultSiteSection(new MockConfig());
        $siteSectionProvider = new MockSiteSectionProvider($defaultSection);
        $section1 = new MockSiteSection("section1", "en", "1", 1, "group1");
        $section2 = new MockSiteSection("section2", "en", "2", 2, "group2");
        $siteSectionProvider->addSiteSections([$section1, $section2]);
        self::container()->setInstance(SiteSectionProviderInterface::class, $siteSectionProvider);

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
            "siteSectionGroup" => $section1->getSectionGroup(),
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
        $siteSectionProvider->setCurrentSiteSection($section2);
        $page->validateSiteSection($kbID);
    }
}
