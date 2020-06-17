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
import { IApiError } from "@vanilla/library/src/scripts/@types/api/core";
import { IUnifySearchFormState, UnifySearchDomain } from "@knowledge/modules/search/unifySearchPageReducer";
import _ from "lodash";

export interface IUnifySearchFormActionProps {
    searchActions: UnifySearchPageActions;
}

const createAction = actionCreatorFactory("@@searchPage");

export default class UnifySearchPageActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static readonly LIMIT_DEFAULT = 10;
    private static readonly ALL_FORM = [
        "query",
        "title",
        "authors",
        "startDate",
        "endDate",
        "domain",
        "includeDeleted",
        "page",
    ];

    private static readonly DISCUSSIONS_FORM = [
        ...UnifySearchPageActions.ALL_FORM,
        "categoryID",
        "tags",
        "followedCategories",
        "includeChildCategories",
        "includeArchivedCategories",
    ];

    // Just a placeholder, to be expanded still
    private static readonly ARTICLES_FORM = [...UnifySearchPageActions.ALL_FORM];

    // Just a placeholder, to be expanded still
    private static readonly CATEGORIES_AND_GROUPS_FORM = [...UnifySearchPageActions.ALL_FORM];

    private static notIsNil = (k: string) => !_.isNil(k);

    private getRecordType(domain: UnifySearchDomain) {
        switch (domain) {
            case UnifySearchDomain.DISCUSSIONS:
                return ["discussion"];
            case UnifySearchDomain.ARTICLES:
                return ["article"];
            case UnifySearchDomain.CATEGORIES_AND_GROUPS:
                return ["group"];
            case UnifySearchDomain.EVERYWHERE:
                return ["discussion", "article", "comment", "group"];
        }
    }

    private getQueryForm(domain: UnifySearchDomain, form: IUnifySearchFormState): Partial<IUnifySearchFormState> {
        const notIsNil = UnifySearchPageActions.notIsNil;
        switch (domain) {
            case UnifySearchDomain.DISCUSSIONS:
                return _.pickBy(_.pick(form, UnifySearchPageActions.DISCUSSIONS_FORM), notIsNil);
            case UnifySearchDomain.ARTICLES:
                return _.pickBy(_.pick(form, UnifySearchPageActions.ARTICLES_FORM), notIsNil);
            case UnifySearchDomain.CATEGORIES_AND_GROUPS:
                return _.pickBy(_.pick(form, UnifySearchPageActions.CATEGORIES_AND_GROUPS_FORM), notIsNil);
            case UnifySearchDomain.EVERYWHERE:
                return _.pickBy(_.pick(form, UnifySearchPageActions.ALL_FORM), notIsNil);
        }
    }

    public static mapDispatchToProps(dispatch): IUnifySearchFormActionProps {
        return {
            searchActions: new UnifySearchPageActions(dispatch, apiv2),
        };
    }

    public static getUnifySearchACs = createAction.async<
        IUnifySearchRequestBody,
        { body: IUnifySearchResponseBody; pagination: ILinkPages },
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

    public static updateUnifyFormAC = createAction<Partial<IUnifySearchFormState>>("UPDATE_UNIFY_FORM");
    public updateUnifyForm = this.bindDispatch(UnifySearchPageActions.updateUnifyFormAC);

    public static setUnifyFormAC = createAction<Partial<IUnifySearchFormState>>("SET_UNIFY_FORM");
    public setUnifyForm = this.bindDispatch(UnifySearchPageActions.setUnifyFormAC);

    public static resetAC = createAction("RESET");
    public reset = this.bindDispatch(UnifySearchPageActions.resetAC);

    public unifySearch = async (
        domain = UnifySearchDomain.EVERYWHERE,
        page = 1,
        limit = UnifySearchPageActions.LIMIT_DEFAULT,
    ) => {
        const { form } = this.getState().knowledge.unifySearchPage;
        const queryForm = this.getQueryForm(domain, form);
        this.setUnifyForm(queryForm);
        // console.log(form);
        // console.log(queryForm);

        const requestOptions: IUnifySearchRequestBody = {
            recordTypes: this.getRecordType(domain),
            query: form.query,
            page,
            limit,
        };

        return await this.getUnifySearch(requestOptions);
    };
}

export function useUnifySearchPageActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new UnifySearchPageActions(dispatch, apiv2), [dispatch]);

    return actions;
}
