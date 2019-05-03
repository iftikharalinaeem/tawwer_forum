/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AxiosResponse } from "axios";
import apiv2 from "@library/apiv2";
import getStore from "@library/redux/getStore";
import ServerActions from "@knowledge/server/ServerActions";

export const deploymentKeyMiddleware = (response: AxiosResponse) => {
    const serverActions = new ServerActions(getStore().dispatch, apiv2);
    if (response.headers && typeof response.headers === "object" && response.headers.vdk) {
        serverActions.setServerDeploymentKey({ result: response.headers.vdk as string });
    }
    return response;
};
