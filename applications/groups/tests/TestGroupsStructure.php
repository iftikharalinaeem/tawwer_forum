<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Addons\Groups;

use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for the groups structure.
 */
class TestGroupsStructure extends AbstractAPIv2Test {

    /**
     * Test that configuration values are properly updated.
     */
    public function testRewriteConfigValues() {
        $config = \Gdn::config();
        $config->removeFromConfig('EnabledApplications.Groups');
        $config->saveToConfig('EnabledApplications.Groups and Events', 'groups');

        require PATH_APPLICATIONS."/groups/settings/structure.php";

        $this->assertEquals(null, $config->get('EnabledApplications.Groups and Events', null));
        $this->assertEquals('groups', $config->get('EnabledApplications.Groups', null));
    }
}
