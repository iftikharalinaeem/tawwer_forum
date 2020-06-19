/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import UnifySearchPageActions, { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import {
    UnifySearchDomain,
    useSearchPageData,
    IUnifySearchFormState,
} from "@knowledge/modules/search/unifySearchPageReducer";
import DiscussionFilter from "@knowledge/modules/search/filters/DiscussionsFilter";
import Button from "@vanilla/library/src/scripts/forms/Button";
import AllContentFilter from "@knowledge/modules/search/filters/AllContentFilter";
import ArticlesFilter from "@knowledge/modules/search/filters/ArticlesFilter";
import { UnifyQueryString } from "@vanilla/library/src/scripts/routing/UnifyQueryString";
import { useHistory, useLocation } from "react-router-dom";
import qs from "qs";
import _ from "lodash";

const filters = {
    [UnifySearchDomain.ALL_CONTENT]: AllContentFilter,
    [UnifySearchDomain.DISCUSSIONS]: DiscussionFilter,
    [UnifySearchDomain.ARTICLES]: ArticlesFilter,
};

export default function UnifySearchForm() {
    const { unifySearch, setUnifyForm, getQueryForm } = useUnifySearchPageActions();
    const { form, results, pages, query } = useSearchPageData();
    const location = useLocation();
    const history = useHistory();

    const [filter, setFilter] = useState(UnifySearchDomain.ALL_CONTENT);
    const Filter = filters[filter];

    const [queryForm, setQueryForm] = useState<IUnifySearchFormState>(form);
    const fillQueryForm = (entry: Partial<IUnifySearchFormState>) => setQueryForm({ ...queryForm, ...entry });

    const onSearch = () => {
        unifySearch(filter);
    };

    useEffect(() => {
        unifySearch(filter);
    }, [filter]);

    useEffect(() => {
        setUnifyForm({ queryForm, domain: filter });
    }, [queryForm]);

    useEffect(() => {
        history.replace({ ...location, search: qs.stringify(query) });
    }, [query]);

    useEffect(() => {
        const { search } = location;
        const queryForm = qs.parse(search.replace(/^\?/, ""));
        const form = _.pickBy(
            _.pick(queryForm, UnifySearchPageActions.ALL_FORM_ENTRIES),
            UnifySearchPageActions.notIsNil,
        );
        // console.log("queryForm: ", queryForm);
        // console.log("form: ", form);
        // Work with user name instead of user ids, so that it's easier to fill
        // out the initial form
        setUnifyForm({ queryForm: form, domain: filter });
    }, []);

    useEffect(() => {
        const { search } = location;
        const queryForm = qs.parse(search.replace(/^\?/, ""));
        // Write a small function here to convert array back to type
        setFilter(UnifySearchDomain.ARTICLES);
    }, []);

    return (
        <div>
            {/* <UnifyQueryString query={query} /> */}
            <Button onClick={() => setFilter(UnifySearchDomain.ALL_CONTENT)}>All Content</Button>
            <Button onClick={() => setFilter(UnifySearchDomain.DISCUSSIONS)}>Discussions</Button>
            <Button onClick={() => setFilter(UnifySearchDomain.ARTICLES)}> Articles </Button>
            <br /> <br />
            <Filter onSearch={onSearch} fillQueryForm={fillQueryForm} />
            <br /> <br /> <br /> <br />
            {/* <div>{JSON.stringify(getQueryForm(filter, queryForm), null, 2)}</div> */}
            <div>{JSON.stringify(form, null, 2)}</div>
            <br /> <br />
            <div>{JSON.stringify(query, null, 2)} </div>
        </div>
    );
}
