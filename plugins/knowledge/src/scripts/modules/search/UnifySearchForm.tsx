/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import UnifySearchPageActions, { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import {
    INITIAL_SEARCH_FORM,
    UnifySearchDomain,
    useSearchPageData,
} from "@knowledge/modules/search/unifySearchPageReducer";
import { SearchIcon } from "@vanilla/library/src/scripts/icons/titleBar";
import QueryString from "@vanilla/library/src/scripts/routing/QueryString";
import { SearchInFilter } from "@vanilla/library/src/scripts/search/SearchInFilter";
import { notEmpty } from "@vanilla/utils";
import _ from "lodash";
import qs from "qs";
import React, { useEffect } from "react";
import { useLocation } from "react-router-dom";

export default function UnifySearchForm() {
    const { unifySearch, updateForm: updateUnifyForm } = useUnifySearchPageActions();
    const { form } = useSearchPageData();
    const location = useLocation();

    useEffect(() => {
        const { search } = location;
        const queryForm = qs.parse(search.replace(/^\?/, ""));
        const form = _.pickBy(_.pick(queryForm, UnifySearchPageActions.ALL_FORM_ENTRIES), notEmpty);
        updateUnifyForm(form);
        // Only for first initialization.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <div>
            <QueryString value={form} defaults={INITIAL_SEARCH_FORM} />
            {/* TODO make this component generic */}
            <SearchInFilter
                activeItem={form.domain}
                filters={[
                    {
                        label: "All Content",
                        data: UnifySearchDomain.ALL_CONTENT,
                        icon: <SearchIcon />,
                    },
                    {
                        label: "Discussions",
                        data: UnifySearchDomain.DISCUSSIONS,
                        icon: <SearchIcon />,
                    },
                    {
                        label: "Articles",
                        data: UnifySearchDomain.ARTICLES,
                        icon: <SearchIcon />,
                    },
                ]}
                setData={newDomain => {
                    updateUnifyForm({ domain: newDomain as UnifySearchDomain });
                    unifySearch();
                }}
            />
            <br /> <br /> <br /> <br />
            <div>{JSON.stringify(form, null, 2)}</div>
        </div>
    );
}
