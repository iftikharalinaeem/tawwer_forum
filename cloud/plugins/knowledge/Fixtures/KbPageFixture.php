<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Fixtures;

use Vanilla\Knowledge\Controllers\Pages\KbPage;

/**
 * Fixture for testing a KBPage.
 */
class KbPageFixture extends KbPage {

    /**
     * Does nothing.
     */
    public function initialize() {
        return;
    }

    /**
     * @inheritdoc
     */
    public function disableSiteSectionValidation(): KbPage {
        return parent::disableSiteSectionValidation();
    }

    /**
     * @inheritdoc
     */
    public function validateSiteSection(int $kbID): KbPage {
        return parent::validateSiteSection($kbID);
    }
}
