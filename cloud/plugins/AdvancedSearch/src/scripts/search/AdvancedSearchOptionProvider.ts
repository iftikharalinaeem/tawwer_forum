/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchOptionProvider } from "@library/contexts/SearchContext";
import { formatUrl } from "@library/utility/appUtils";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ISearchOptionData } from "@library/features/search/SearchOption";
import { AxiosResponse } from "axios";
import apiv2 from "@library/apiv2";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import pDebounce from "p-debounce";
import { NEW_SEARCH_PAGE_ENABLED } from "@vanilla/library/src/scripts/search/searchConstants";

interface IAdvancedSearchQuery {
    query: string;
    limit?: number;
    page?: number;
    expandBody?: boolean;
    expand?: Array<"insertUser" | "breadcrumbs">;
}

interface IAdvancedSearchResult {
    name: string;
    recordID: number;
    recordType: string;
    url: string;
    breadcrumbs?: ICrumb[];
    categoryID: number;
    commentID: number;
    dateInserted: string;
    dateUpdated: string;
    discussionID: number;
    groupID: number;
    insertUserID: number;
    score: number;
    type: string;
    updateUserID: number;
}

/**
 * Advanced Search implementation of autocomplete using sphinx.
 */
export class AdvancedSearchOptionProvider implements ISearchOptionProvider {
    public supportsAutoComplete = true;

    /**
     * Simple data loading function for the search bar/react-select.
     */
    private fetchSearch = async (value: string, options = {}): Promise<Array<IComboBoxOption<ISearchOptionData>>> => {
        const queryObj: IAdvancedSearchQuery = {
            query: value,
            expandBody: false,
            expand: ["breadcrumbs"],
            limit: 10,
        };
        const response: AxiosResponse<IAdvancedSearchResult[]> = await apiv2.get(`/search`, { params: queryObj });
        return response.data.map(result => {
            const data: ISearchOptionData = {
                crumbs: result.breadcrumbs ?? [],
                name: result.name,
                dateUpdated: result.dateUpdated,
                url: result.url,
            };
            return {
                label: result.name,
                value: result.name,
                data,
            };
        });
    };

    /**
     * A debounced version of the fetchSearch() function.
     */
    private debounceFetchSearch = pDebounce(this.fetchSearch, 100);

    /**
     * Get autocomplete results.
     *
     * This has an early bailout for an empty string because initially focusing the input can cause
     * a change event to be fired with an empty value.
     *
     * @see https://github.com/JedWatson/react-select/issues/614#issuecomment-380763225
     */
    public autocomplete = (query: string, options = {}) => {
        if (query === "") {
            return Promise.resolve([]);
        }

        return this.debounceFetchSearch(query, options);
    };

    public makeSearchUrl = (query: string) => {
        const queryParamName = NEW_SEARCH_PAGE_ENABLED ? "query" : "search";
        return formatUrl(`/search?${queryParamName}=${query}`, true);
    };
}
