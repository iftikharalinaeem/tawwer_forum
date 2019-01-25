/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/state/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IApiError } from "@library/@types/api";

const actionCreator = actionCreatorFactory("@@knowledgeBases");

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseActions extends ReduxActions {
    public static readonly GET_ACS = actionCreator.async<undefined, IKnowledgeBase[], IApiError>("GET_ALL");

    public getAll = () => {
        const thunk = bindThunkAction(KnowledgeBaseActions.GET_ACS, async () => {
            const response = await this.api.get("/knowledge-bases");
            return response.data;
        })();
        return this.dispatch(thunk);
    };
}
