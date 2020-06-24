/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import ReduxActions from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";

const createAction = actionCreatorFactory("@@server");

interface ISetDeploymentKey {
    result: string | null;
}

export default class ServerActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static setLocalDeploymentKey = createAction<ISetDeploymentKey>("SET_LOCAL_DEPLOYMENT_KEY");
    public setLocalDeploymentKey = this.bindDispatch(ServerActions.setLocalDeploymentKey);

    public static setServerDeploymentKey = createAction<ISetDeploymentKey>("SET_SERVER_DEPLOYMENT_KEY");
    public setServerDeploymentKey = this.bindDispatch(ServerActions.setServerDeploymentKey);
}
