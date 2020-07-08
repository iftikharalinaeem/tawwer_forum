<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Vanilla\Web\Pagination;


class FlatApiPaginationIterator extends ApiPaginationIterator {
    protected function internalGenerator(): \Generator {
        foreach (parent::internalGenerator() as $page) {
            yield from $page;
        }
    }
}
