/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchRequestBody, ISearchResponseBody } from "@knowledge/@types/api/search";
import { ISearchFormState, SearchDomain, useSearchPageData } from "@knowledge/modules/search/searchPageReducer";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { IApiError, PublishStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { useMemo, useCallback } from "react";
import { useDispatch } from "react-redux";
import actionCreatorFactory from "typescript-fsa";
import { getCurrentLocale } from "@vanilla/i18n";

export interface ISearchFormActionProps {
    searchActions: SearchPageActions;
}

const createAction = actionCreatorFactory("@@searchPage");

/**
 * Action class for the search page/form.
 */
export default class SearchPageActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static readonly LIMIT_DEFAULT = 10;

    // Action constants
    public static readonly GET_SEARCH_REQUEST = "@@searchPage/GET_SEARCH_REQUEST";
    public static readonly GET_SEARCH_RESPONSE = "@@searchPage/GET_SEARCH_RESPONSE";
    public static readonly GET_SEARCH_ERROR = "@@searchPage/GET_SEARCH_ERROR";

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
    public static getSearchACs = createAction.async<
        ISearchRequestBody,
        { body: ISearchResponseBody; pagination: ILinkPages },
        IApiError
    >("GET_SEARCH");
    /**
     * Thunk for performing a search.
     */
    private getSearch(params: ISearchRequestBody) {
        const { page, limit } = params;
        params.page = page || 1;
        params.limit = limit || SearchPageActions.LIMIT_DEFAULT;

        const thunk = bindThunkAction(SearchPageActions.getSearchACs, async () => {
            const response = await this.api.get("/knowledge/search", { params });
            return {
                body: response.data,
                pagination: SimplePagerModel.parseLinkHeader(response.headers["link"], "page"),
            };
        })(params);

        return this.dispatch(thunk);
    }

    public static updateFormAC = createAction<Partial<ISearchFormState>>("UPDATE_FORM");
    public updateForm = this.bindDispatch(SearchPageActions.updateFormAC);

    public static resetAC = createAction("RESET");
    public reset = this.bindDispatch(SearchPageActions.resetAC);

    /**
     * Perform a search with the values in the form.
     */
    public search = async (page?: number, limit?: number) => {
        const { form } = this.getState().knowledge.searchPage;

        if (page == null) {
            page = form.page;
        }

        const statuses = [PublishStatus.PUBLISHED];
        if (form.includeDeleted) {
            statuses.push(PublishStatus.DELETED);
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

        if (form.domain === SearchDomain.EVERYWHERE && form.communityCategory) {
            const categoryID = form.communityCategory.value.toString();
            query.categoryIDs = [parseInt(categoryID, 10)];
        }

        if (form.kb) {
            const knowledgeBaseID = form.kb.value.toString();
            query.knowledgeBaseID = parseInt(knowledgeBaseID, 10);
        }

        if (form.siteSectionGroup && form.siteSectionGroup !== "all") {
            // Backend doesn't actually support an all parameter. Rather, all is the default.
            query.siteSectionGroup = form.siteSectionGroup;
        }

        const requestOptions: ISearchRequestBody = {
            ...query,
            updateUserIDs: form.authors.map(author => author.value as number),
            global: form.domain === SearchDomain.EVERYWHERE,
            statuses,
            dateUpdated,
            expand: ["users", "breadcrumbs"],
            locale: getCurrentLocale(),
            page,
            limit,
        };

        return await this.getSearch(requestOptions);
    };
}

export function useSearchPageActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return new SearchPageActions(dispatch, apiv2);
    }, [dispatch]);

    return actions;
}
