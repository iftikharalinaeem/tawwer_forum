<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbProviderInterface;
use Vanilla\Contracts\RecordInterface;

 /**
  * Provide capabilities for generating a category breadcrumb.
 */
class CategoryBreadcrumbProvider implements BreadcrumbProviderInterface {

    /**
     * @inheritDoc
     */
    public function getForRecord(RecordInterface $record): array {
        $breadcrumbs = [];
        foreach (["One", "Two"] as $name) {
            $crumb = new Breadcrumb();
            $crumb->setName($name);
            $crumb->setUrl("#");
            $breadcrumbs[] = $crumb;
        }

        return $breadcrumbs;
    }

    /**
     * @inheritDoc
     */
    public static function getRecordType(): string {
        return Navigation::RECORD_TYPE_CATEGORY;
    }
}
