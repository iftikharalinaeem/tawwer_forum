<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx\Tests\Utils;

trait SphinxTestTrait {
   /** @var bool */
   protected static $sphinxReindexed;

   /** @var array */
   protected static $dockerResponse;

   public static function sphinxReindex() {
      $sphinxHost = c('Plugins.Sphinx.Server');
      exec('curl -s '.$sphinxHost.':9399', $dockerResponse);
      self::$dockerResponse = $dockerResponse;
      self::$sphinxReindexed = ('Sphinx reindexed.' === end(self::$dockerResponse));
      sleep(1);

      if (!self::$sphinxReindexed) {
         throw new \Exception('Can\'t reindex Sphinx indexes!'."\n".end(self::$dockerResponse));
      }
   }

   protected function assertSphinxReindexed() {
      if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!'."\n".end(self::$dockerResponse));
      }
   }
}
