/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchOptionData } from "@library/features/search/SearchOption";
import { ISearchOptionProvider } from "@library/contexts/SearchContext";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { AxiosResponse } from "axios";
import apiv2 from "@library/apiv2";
import qs from "qs";
import { formatUrl, getSiteSection } from "@library/utility/appUtils";
import pDebounce from "p-debounce";
import { ISearchRequestBody, ISearchResult } from "@knowledge/@types/api/search";
import { PublishStatus } from "@library/@types/api/core";
import { getCurrentLocale } from "@vanilla/i18n";

export default class KnowledgeSearchProvider implements ISearchOptionProvider {
    /**
     * Simple data loading function for the search bar/react-select.
     */
    private fetchSearch = async (value: string, options = {}): Promise<Array<IComboBoxOption<ISearchOptionData>>> => {
        const locale = getCurrentLocale();
        const siteSection = getSiteSection();

        const queryObj: ISearchRequestBody = {
            all: value,
            statuses: [PublishStatus.PUBLISHED],
            limit: 10,
            expand: ["breadcrumbs"],
            locale: locale,
            siteSectionGroup: siteSection.sectionGroup,
            ...options,
        };
        const query = qs.stringify(queryObj);
        const response: AxiosResponse<ISearchResult[]> = await apiv2.get(`/knowledge/search?${query}`);
        return response.data.map(result => {
            const data: ISearchOptionData = {
                crumbs: result.breadcrumbs,
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

    public makeSearchUrl(query: string): string {
        return `/kb/search?query=${encodeURIComponent(query)}`;
    }
}
