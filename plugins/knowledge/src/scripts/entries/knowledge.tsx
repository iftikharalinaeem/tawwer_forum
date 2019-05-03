/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

// Import stylesheets
import "../../scss/knowledge-styles.scss";

// Vendors
import React from "react";
import ReactDOM from "react-dom";
import { forceRenderStyles } from "typestyle";
import { AppContainer } from "react-hot-loader";

// Our own libraries
import apiv2 from "@library/apiv2";
import { onReady } from "@library/utility/appUtils";
import { registerReducer } from "@library/redux/reducerRegistry";
import { debug } from "@library/utility/utils";
import { getMeta } from "@library/utility/appUtils";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import ConversationsModel from "@library/features/conversations/ConversationsModel";
import { initAllUserContent } from "@library/content";

// Knowledge Modules
import { deploymentKeyMiddleware } from "@knowledge/server/deploymentKeyMiddleware";
import rootReducer from "@knowledge/state/reducer";
import KnowledgeApp from "@knowledge/KnowledgeApp";
import { serverReducer } from "@knowledge/server/serverReducer";

debug(getMeta("context.debug"));

const deploymentKeyInterceptor = apiv2.interceptors.response.use(deploymentKeyMiddleware);

const render = () => {
    const app = document.querySelector("#app");
    ReactDOM.render(
        <AppContainer>
            <KnowledgeApp />
        </AppContainer>,
        app,
    );
    forceRenderStyles();
};

onReady(() => {
    initAllUserContent();
    registerReducer("knowledge", rootReducer);
    registerReducer("notifications", new NotificationsModel().reducer);
    registerReducer("conversations", new ConversationsModel().reducer);
    registerReducer("server", serverReducer);
    render();
});

if (module.hot) {
    module.hot.accept("@knowledge/KnowledgeApp", () => {
        render();
    });
}
