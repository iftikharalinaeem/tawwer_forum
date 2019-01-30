/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import SearchPageModel, { ISearchFormState, SearchDomain } from "@knowledge/modules/search/SearchPageModel";
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

    private static readonly LIMIT_DEFAULT = 10;

    // Sum of all action types.
    public static readonly ACTION_TYPES:
        | ReturnType<typeof SearchPageActions.updateFormAC>
        | ReturnType<typeof SearchPageActions.resetAC>
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

    public static readonly RESET = "@@searchPage/RESET";

    /**
     * Reset to initial state.
     */
    private static resetAC() {
        return ReduxActions.createAction(SearchPageActions.RESET);
    }

    public reset = this.bindDispatch(SearchPageActions.resetAC);

    /**
     * Perform a search with the values in the form.
     */
    public search = async (page?: number, limit?: number) => {
        const form = SearchPageModel.stateSlice(this.getState()).form;

        const statuses = [ArticleStatus.PUBLISHED];
        if (form.includeDeleted) {
            statuses.push(ArticleStatus.DELETED);
        }

        // Convert start/endDate into format for our API.
        let dateUpdated: string | undefined;
        if (form.startDate && form.endDate) {
            if (form.startDate === form.endDate) {
                // Simple equality.
                dateUpdated = form.startDate;
            } else {
                // Date range
                dateUpdated = `[${form.startDate},${form.endDate}]`;
            }
        } else if (form.startDate) {
            // Only start date
            dateUpdated = `>=${form.startDate}`;
        } else if (form.endDate) {
            // Only end date.
            dateUpdated = `<=${form.endDate}`;
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
            global: form.domain === SearchDomain.EVERYWHERE,
            statuses,
            dateUpdated,
            expand: ["user", "category"],
        };

        return await this.getSearch(requestOptions, page, limit);
    };

    /**
     * Thunk for performing a search.
     */
    private getSearch(request: ISearchRequestBody, page?: number, limit?: number) {
        request.page = page || 1;
        request.limit = limit || SearchPageActions.LIMIT_DEFAULT;

        return this.dispatchApi<ISearchResponseBody>(
            "get",
            "/knowledge/search",
            SearchPageActions.getSearchACs,
            request,
        );
    }
}
