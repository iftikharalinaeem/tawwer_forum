/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUnifySearchRequestBody, IUnifySearchResponseBody } from "@knowledge/@types/api/unifySearch";
import apiv2 from "@library/apiv2";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { useMemo } from "react";
import { useDispatch } from "react-redux";
import actionCreatorFactory from "typescript-fsa";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { IApiError, PublishStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { IUnifySearchFormState, UnifySearchDomain } from "@knowledge/modules/search/unifySearchPageReducer";
import _ from "lodash";
import { getCurrentLocale } from "@vanilla/i18n";
import { notEmpty } from "@vanilla/utils";

export interface IUnifySearchFormActionProps {
    searchActions: UnifySearchPageActions;
}

interface INotInForm {
    recordTypes: string[];
    page: number;
    limit: number;
    expandBody: boolean;
    dateInserted?: string;
    insertUserIDs?: number[];
    locale?: string;
    expand?: string[];
    // statuses?: PublishStatus[];
}

const createAction = actionCreatorFactory("@@unifySearchPage");

export default class UnifySearchPageActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static readonly LIMIT_DEFAULT = 10;

    // QUERY_KEYS_IN_FORM + QUERY_KEYS_NOT_IN_FORM = IUnifySearchRequestBody
    private static readonly QUERY_KEYS_IN_FORM = [
        "query",
        "types",
        "discussionID",
        "categoryID",
        "followedCategories",
        "includeChildCategories",
        "includeArchivedCategories",
        "name",
        "insertUserNames",
        "tags",
        "tagOperator",
        "expand",
    ];
    // This matches INotInForm
    private static readonly QUERY_KEYS_NOT_IN_FORM = [
        "recordTypes",
        "page",
        "limit",
        "expandBody",
        "locale",
        "insertUserIDs",
        "expand",
        "dateInserted",
        // "statuses",
    ];

    public static toQuery(form: IUnifySearchFormState, extraParams?: INotInForm): Partial<IUnifySearchRequestBody> {
        const paramsInForm = _.pick(form, UnifySearchPageActions.QUERY_KEYS_IN_FORM);

        const paramsNotInForm = _.pickBy(_.pick(extraParams, UnifySearchPageActions.QUERY_KEYS_NOT_IN_FORM), notEmpty);

        return { ...paramsInForm, ...paramsNotInForm };
    }

    private static readonly ALL_FORM = ["query", "name", "authors", "startDate", "endDate", "page"];

    private static readonly DISCUSSIONS_FORM = [
        ...UnifySearchPageActions.ALL_FORM,
        "domain",
        "categoryID",
        "tags",
        "followedCategories",
        "includeChildCategories",
        "includeArchivedCategories",
        "insertUserIDs",
    ];

    private static readonly ARTICLES_FORM = [
        ...UnifySearchPageActions.ALL_FORM,
        "knowledgeBaseID",
        "includeDeleted",
        "insertUserIDs",
    ];

    // Just a placeholder, to be expanded still
    private static readonly CATEGORIES_AND_GROUPS_FORM = [...UnifySearchPageActions.ALL_FORM];

    public static readonly ALL_FORM_ENTRIES = [
        ...UnifySearchPageActions.ALL_FORM,
        ...UnifySearchPageActions.DISCUSSIONS_FORM,
        ...UnifySearchPageActions.ARTICLES_FORM,
        ...UnifySearchPageActions.CATEGORIES_AND_GROUPS_FORM,
    ];

    public static getRecordType(domain: UnifySearchDomain) {
        switch (domain) {
            case UnifySearchDomain.DISCUSSIONS:
                return ["discussion"];
            case UnifySearchDomain.ARTICLES:
                return ["article"];
            case UnifySearchDomain.CATEGORIES_AND_GROUPS:
                return ["group"];
            case UnifySearchDomain.ALL_CONTENT:
                return ["discussion", "article", "comment", "group"];
        }
    }

    private getStatuses(includeDeleted?: boolean): PublishStatus[] {
        return includeDeleted ? [PublishStatus.PUBLISHED, PublishStatus.DELETED] : [PublishStatus.PUBLISHED];
    }

    public getQueryForm(
        domain: UnifySearchDomain,
        form: IUnifySearchFormState | IUnifySearchRequestBody,
    ): Partial<IUnifySearchFormState> {
        const notIsNil = notEmpty;
        switch (domain) {
            case UnifySearchDomain.DISCUSSIONS:
                return _.pickBy(_.pick(form, UnifySearchPageActions.DISCUSSIONS_FORM), notIsNil);
            case UnifySearchDomain.ARTICLES:
                return _.pickBy(_.pick(form, UnifySearchPageActions.ARTICLES_FORM), notIsNil);
            case UnifySearchDomain.CATEGORIES_AND_GROUPS:
                return _.pickBy(_.pick(form, UnifySearchPageActions.CATEGORIES_AND_GROUPS_FORM), notIsNil);
            case UnifySearchDomain.ALL_CONTENT:
                return _.pickBy(_.pick(form, UnifySearchPageActions.ALL_FORM), notIsNil);
        }
    }

    public static getUnifySearchACs = createAction.async<
        IUnifySearchRequestBody,
        { body: IUnifySearchResponseBody[]; pagination: ILinkPages },
        IApiError
    >("GET_UNIFY_SEARCH");

    private getUnifySearch(params: IUnifySearchRequestBody) {
        const thunk = bindThunkAction(UnifySearchPageActions.getUnifySearchACs, async () => {
            const response = await this.api.get("/search", { params });
            return {
                body: response.data,
                pagination: SimplePagerModel.parseLinkHeader(response.headers["link"], "page"),
            };
        })(params);

        return this.dispatch(thunk);
    }

    public static updateFormAC = createAction<Partial<IUnifySearchFormState>>("UPDATE_FORM");
    public updateForm = this.bindDispatch(UnifySearchPageActions.updateFormAC);

    public static resetAC = createAction("RESET");
    public reset = this.bindDispatch(UnifySearchPageActions.resetAC);

    public unifySearch = async () => {
        const { form } = this.getState().knowledge.unifySearchPage;
        const { domain } = form;
        const queryForm = this.getQueryForm(domain, form);
        queryForm.domain = domain;

        // Convert start/endDate into format for our API.
        let dateInserted: string | undefined;
        if (form.startDate && form.endDate) {
            if (form.startDate === form.endDate) {
                // Simple equality.
                dateInserted = form.startDate;
            } else {
                // Date range
                dateInserted = `[${form.startDate},${form.endDate}]`;
            }
        } else if (form.startDate) {
            // Only start date
            dateInserted = `>=${form.startDate}`;
        } else if (form.endDate) {
            // Only end date.
            dateInserted = `<=${form.endDate}`;
        }

        const extraParams: INotInForm = {
            recordTypes: UnifySearchPageActions.getRecordType(domain),
            page: form.page,
            limit: UnifySearchPageActions.LIMIT_DEFAULT,
            expandBody: true,
            locale:
                domain === UnifySearchDomain.ARTICLES || domain === UnifySearchDomain.ALL_CONTENT
                    ? getCurrentLocale()
                    : undefined,
            insertUserIDs:
                form.authors && form.authors.length ? form.authors.map(author => author.value as number) : undefined,
            dateInserted,
            expand: ["insertUser", "breadcrumbs"],
        };
        const requestOptions: IUnifySearchRequestBody = UnifySearchPageActions.toQuery(queryForm, extraParams);

        return await this.getUnifySearch(requestOptions);
    };
}

export function useUnifySearchPageActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new UnifySearchPageActions(dispatch, apiv2), [dispatch]);

    return actions;
}
