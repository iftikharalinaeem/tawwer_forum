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

// Our own libraries
import { onReady } from "@library/dom/appUtils";
import { registerReducer } from "@library/redux/reducerRegistry";
import { debug } from "@library/utility/utils";
import { getMeta } from "@library/dom/appUtils";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import ConversationsModel from "@library/features/conversations/ConversationsModel";

// Knowledge Modules
import rootReducer from "@knowledge/state/reducer";
import KnowledgeApp from "@knowledge/KnowledgeApp";
import { initAllUserContent } from "@library/content";
import { forceRenderStyles } from "typestyle";

debug(getMeta("context.debug"));

onReady(() => {
    initAllUserContent();
    registerReducer("knowledge", rootReducer);
    registerReducer("notifications", new NotificationsModel().reducer);
    registerReducer("conversations", new ConversationsModel().reducer);
    const app = document.querySelector("#app");
    ReactDOM.render(<KnowledgeApp />, app);
    forceRenderStyles();
});
