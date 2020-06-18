/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import {
    UnifySearchDomain,
    useSearchPageData,
    IUnifySearchFormState,
    INITIAL_SEARCH_FORM,
} from "@knowledge/modules/search/unifySearchPageReducer";
import DiscussionFilter from "@knowledge/modules/search/filters/DiscussionsFilter";
import Button from "@vanilla/library/src/scripts/forms/Button";
import AllContentFilter from "@knowledge/modules/search/filters/AllContentFilter";
import ArticlesFilter from "@knowledge/modules/search/filters/ArticlesFilter";
import { UnifyQueryString } from "@vanilla/library/src/scripts/routing/UnifyQueryString";

const filters = {
    [UnifySearchDomain.ALL_CONTENT]: AllContentFilter,
    [UnifySearchDomain.DISCUSSIONS]: DiscussionFilter,
    [UnifySearchDomain.ARTICLES]: ArticlesFilter,
};

export default function UnifySearchForm() {
    const { unifySearch, setUnifyForm, getQueryForm } = useUnifySearchPageActions();
    const { form, results, pages } = useSearchPageData();

    const [filter, setFilter] = useState(UnifySearchDomain.ALL_CONTENT);
    const Filter = filters[filter];

    const [liveForm, setLiveForm] = useState<IUnifySearchFormState>(form);
    const fillLiveForm = (entry: Partial<IUnifySearchFormState>) => setLiveForm({ ...liveForm, ...entry });

    const onSearch = () => {
        setUnifyForm(liveForm);
        unifySearch(filter);
    };

    useEffect(() => {
        unifySearch(filter);
    }, [filter]);

    return (
        <div>
            <UnifyQueryString />
            <Button onClick={() => setFilter(UnifySearchDomain.ALL_CONTENT)}>All Content</Button>
            <Button onClick={() => setFilter(UnifySearchDomain.DISCUSSIONS)}>Discussions</Button>
            <Button onClick={() => setFilter(UnifySearchDomain.ARTICLES)}> Articles </Button>
            <br /> <br />
            <Filter onSearch={onSearch} fillLiveForm={fillLiveForm} />
            <br /> <br /> <br /> <br />
            {/* <div>{JSON.stringify(getQueryForm(filter, liveForm), null, 2)}</div> */}
            <div>{JSON.stringify(form, null, 2)}</div>
        </div>
    );
}
