/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import SearchPageModel, { ISearchFormState } from "@knowledge/modules/search/SearchPageModel";
import apiv2 from "@library/apiv2";
import { ISearchResponseBody, ISearchRequestBody, ArticleStatus } from "@knowledge/@types/api";

export interface ISearchFormActionProps {
    searchActions: SearchPageActions;
}

/**
 * Action class for the search page/form.
 */
export default class SearchPageActions extends ReduxActions {
    // Action constants
    public static readonly GET_SEARCH_REQUEST = "@@searchPage/GET_SEARCH_REQUEST";
    public static readonly GET_SEARCH_RESPONSE = "@@searchPage/GET_SEARCH_RESPONSE";
    public static readonly GET_SEARCH_ERROR = "@@searchPage/GET_SEARCH_ERROR";
    public static readonly UPDATE_FORM = "@@searchPage/UPDATE_FORM";

    // Sum of all action types.
    public static readonly ACTION_TYPES:
        | ReturnType<typeof SearchPageActions.updateFormAC>
        | ActionsUnion<typeof SearchPageActions.getSearchACs>;

    /**
     * Mapping function for react-redux.
     */
    public static mapDispatchToProps(dispatch): ISearchFormActionProps {
        return {
            searchActions: new SearchPageActions(dispatch, apiv2),
        };
    }

    /**
     * Action creators for search.
     */
    private static getSearchACs = ReduxActions.generateApiActionCreators(
        SearchPageActions.GET_SEARCH_REQUEST,
        SearchPageActions.GET_SEARCH_RESPONSE,
        SearchPageActions.GET_SEARCH_ERROR,
        {} as ISearchResponseBody,
        {} as ISearchRequestBody,
    );

    /**
     * Create an action for updating the form.
     *
     * @param updates A partial form value.
     */
    private static updateFormAC(updates: Partial<ISearchFormState>) {
        return ReduxActions.createAction(SearchPageActions.UPDATE_FORM, { updates });
    }

    public updateForm = this.bindDispatch(SearchPageActions.updateFormAC);

    /**
     * Perform a search with the values in the form.
     */
    public search = async () => {
        const form = SearchPageModel.stateSlice(this.getState()).form;

        const statuses = [ArticleStatus.PUBLISHED];
        if (form.includeDeleted) {
            statuses.push(ArticleStatus.DELETED);
        }

        // Convert start/endDate into format for our API.
        let dateUpdated: string | undefined;
        if (form.startDate && form.endDate) {
            dateUpdated = `[${form.startDate},${form.endDate}]`;
        } else if (form.startDate) {
            dateUpdated = `>${form.startDate}`;
        } else if (form.endDate) {
            dateUpdated = `<${form.endDate}`;
        }

        // Put together the search query.
        const query: ISearchRequestBody = {};
        if (!form.title) {
            query.all = form.query;
        } else {
            query.name = form.title;
            query.body = form.query;
        }

        const requestOptions: ISearchRequestBody = {
            ...query,
            updateUserIDs: form.authors.map(author => author.value as number),
            statuses,
            dateUpdated,
            expand: ["user", "category"],
        };

        return await this.getSearch(requestOptions);
    };

    /**
     * Thunk for performing a search.
     */
    private getSearch(request: ISearchRequestBody) {
        return this.dispatchApi<ISearchResponseBody>(
            "get",
            "/knowledge/search",
            SearchPageActions.getSearchACs,
            request,
        );
    }
}
