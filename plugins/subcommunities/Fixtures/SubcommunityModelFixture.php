<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Fixtures;

use SubcommunityModel;

/**
 * Wrapper to improve testability of original Subcommunity model.
 */
class SubcommunityModelFixture extends SubcommunityModel {

    /**
     * Reset all known static properties on the instance.
     *
     * @return void
     */
    public static function resetStaticProperties(): void {
        self::$all = null;
        self::$available = null;
        self::$current = null;
        self::$instance = null;
    }
}
