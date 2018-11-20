import { ISearchOptionData } from "@library/components/search/SearchOption";
import { ISearchOptionProvider } from "@library/contexts/ApiContext";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";
import { AxiosResponse } from "axios";
import { ISearchResult } from "@knowledge/@types/api";
import apiv2 from "@library/apiv2";
import qs from "qs";
import { formatUrl } from "@library/application";

/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

export default class KnowledgeSearchProvider implements ISearchOptionProvider {
    /**
     * Simple data loading function for the search bar/react-select.
     */
    public async autocomplete(value: string): Promise<Array<IComboBoxOption<ISearchOptionData>>> {
        const queryObj = {
            name: value,
            expand: ["user", "category"],
        };
        const query = qs.stringify(queryObj);
        const response: AxiosResponse<ISearchResult[]> = await apiv2.get(`/knowledge/search?${query}`);
        return response.data.map(result => {
            const data: ISearchOptionData = {
                crumbs: result.knowledgeCategory!.breadcrumbs,
                name: result.name,
                dateUpdated: result.dateUpdated,
            };
            return {
                label: result.name,
                value: result.name,
                data,
            };
        });
    }

    public makeSearchUrl(query: string): string {
        return formatUrl(`/kb/search?query=${encodeURIComponent(query)}`);
    }
}
