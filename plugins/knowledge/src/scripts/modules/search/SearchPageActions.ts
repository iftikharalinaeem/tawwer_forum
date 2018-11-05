/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import { ISearchFormState } from "@knowledge/modules/search/SearchPageModel";
import apiv2 from "@library/apiv2";
import { ISearchResponseBody, ISearchRequestBody } from "@knowledge/@types/api";

export interface ISearchFormActionProps {
    searchActions: SearchPageActions;
}

export default class SearchPageActions extends ReduxActions {
    public static readonly GET_SEARCH_REQUEST = "@@searchPage/GET_SEARCH_REQUEST";
    public static readonly GET_SEARCH_RESPONSE = "@@searchPage/GET_SEARCH_RESPONSE";
    public static readonly GET_SEARCH_ERROR = "@@searchPage/GET_SEARCH_ERROR";

    public static readonly UPDATE_FORM = "@@searchPage/UPDATE_FORM";

    public static readonly ACTION_TYPES:
        | ReturnType<typeof SearchPageActions.updateFormAC>
        | ActionsUnion<typeof SearchPageActions.getSearchACs>;

    public static mapDispatchToProps(dispatch): ISearchFormActionProps {
        return {
            searchActions: new SearchPageActions(dispatch, apiv2),
        };
    }

    private static getSearchACs = ReduxActions.generateApiActionCreators(
        SearchPageActions.GET_SEARCH_REQUEST,
        SearchPageActions.GET_SEARCH_RESPONSE,
        SearchPageActions.GET_SEARCH_ERROR,
        {} as ISearchResponseBody,
        {} as ISearchRequestBody,
    );

    private static updateFormAC(updates: Partial<ISearchFormState>) {
        return ReduxActions.createAction(SearchPageActions.UPDATE_FORM, { updates });
    }

    public updateForm = this.bindDispatch(SearchPageActions.updateFormAC);

    public async search() {
        const result = await this.getSearch({});
    }

    private getSearch(request: ISearchRequestBody) {
        return this.dispatchApi<ISearchResponseBody>(
            "get",
            "/knowledge/search",
            SearchPageActions.getSearchACs,
            request,
        );
    }
}
