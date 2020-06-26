<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx\Tests\Utils;

/**
 * Adds a method to re-index sphinx for use in tests.
 */
trait SphinxTestTrait {
    /** @var bool */
    protected static $sphinxReindexed;

    /** @var array */
    protected static $dockerResponse;

    /**
     * Reindex sphinx.
     */
    public static function sphinxReindex() {
        $sphinxHost = c('Plugins.Sphinx.Server');
        exec('curl ' . $sphinxHost . ':9399', $dockerResponse);
        self::$dockerResponse = $dockerResponse;
        self::$sphinxReindexed = ('Sphinx reindexed.' === end(self::$dockerResponse));
        sleep(1);

        if (!self::$sphinxReindexed) {
            throw new \Exception('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }
    }

    /**
     * Assert that sphinx has been properly re-indexed.
     */
    protected function assertSphinxReindexed() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }
    }
}
