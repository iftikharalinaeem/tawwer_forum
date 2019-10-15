/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IKnowledgeBase, KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";

const actionCreator = actionCreatorFactory("@@knowledgeBases");

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseActions extends ReduxActions {
    public static readonly GET_ACS = actionCreator.async<{ status: KnowledgeBaseStatus }, IKnowledgeBase[], IApiError>(
        "GET_ALL",
    );

    public getAll = (status: KnowledgeBaseStatus) => {
        const thunk = bindThunkAction(KnowledgeBaseActions.GET_ACS, async () => {
            const response = await this.api.get(`/knowledge-bases?expand=all&status=${status}`);
            return response.data;
        })({ status });
        return this.dispatch(thunk);
    };
}

export function useKnowledgeBaseActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new KnowledgeBaseActions(dispatch, apiv2), [dispatch]);
    return actions;
}
