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

    public search = async () => {
        const form = SearchPageModel.stateSlice(this.getState()).form;
        console.log(form);
        const statuses = [ArticleStatus.PUBLISHED];
        if (form.includeDeleted) {
            statuses.push(ArticleStatus.DELETED);
        }

        let dateUpdated: string | undefined = undefined;
        if (form.startDate && form.endDate) {
            dateUpdated = `[${form.startDate},${form.endDate}]`;
        } else if (form.startDate) {
            dateUpdated = `>${form.startDate}`;
        } else if (form.endDate) {
            dateUpdated = `<${form.endDate}`;
        }

        const query: any = {};
        if (!form.title) {
            query.both = form.query;
        } else {
            query.title = form.title;
            query.body = form.query;
        }

        const requestOptions: ISearchRequestBody = {
            ...query,
            updateUserIDs: form.authors.map(author => author.value as number),
            statuses,
            dateUpdated,
            expand: ["user", "category"],
        };

        console.log(requestOptions);

        return await this.getSearch(requestOptions);
    };

    private getSearch(request: ISearchRequestBody) {
        return this.dispatchApi<ISearchResponseBody>(
            "get",
            "/knowledge/search",
            SearchPageActions.getSearchACs,
            request,
        );
    }
}
