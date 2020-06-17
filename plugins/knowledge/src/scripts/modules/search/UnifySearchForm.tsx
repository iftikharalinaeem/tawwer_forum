/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import { UnifySearchDomain, useSearchPageData } from "@knowledge/modules/search/unifySearchPageReducer";
import DiscussionFilter from "@knowledge/modules/search/filters/DiscussionsFilter";
import Button from "@vanilla/library/src/scripts/forms/Button";
import AllContentFilter from "@knowledge/modules/search/filters/AllContentFilter";
import ArticlesFilter from "@knowledge/modules/search/filters/ArticlesFilter";

const filters = {
    [UnifySearchDomain.EVERYWHERE]: AllContentFilter,
    [UnifySearchDomain.DISCUSSIONS]: DiscussionFilter,
    [UnifySearchDomain.ARTICLES]: ArticlesFilter,
};

export default function UnifySearchForm() {
    const { unifySearch, updateUnifyForm } = useUnifySearchPageActions();
    updateUnifyForm({ query: "discussion" });

    const [filter, setFilter] = useState(UnifySearchDomain.EVERYWHERE);
    const Filter = filters[filter];

    useEffect(() => {
        unifySearch(filter);
    }, [filter]);

    return (
        <div>
            <Button onClick={() => setFilter(UnifySearchDomain.EVERYWHERE)}>All Content</Button>
            <Button onClick={() => setFilter(UnifySearchDomain.DISCUSSIONS)}>Discussions</Button>
            <Button onClick={() => setFilter(UnifySearchDomain.ARTICLES)}> Articles </Button>
            <Filter />
        </div>
    );
}
