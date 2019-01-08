<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge;

use Vanilla\Knowledge\Models\Breadcrumb;

trait DummyBreadcrumbTrait {
    /**
     * Get dummy breadcrumb data.
     *
     * @return array Returns array of dummy breadcrumb data
     */
    public static function getDummyBreadcrumbData(): array {
        return [
            new Breadcrumb('Books', 'https://example.com/books'),
            new Breadcrumb('Authors', 'https://example.com/books/authors'),
            new Breadcrumb('Ann Leckie', 'https://example.com/books/authors/annleckie'),
            new Breadcrumb('Ancillary Justice', 'https://example.com/books/authors/ancillaryjustice'),
        ];
    }
}
