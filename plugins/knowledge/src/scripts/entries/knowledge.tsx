/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

// Import stylesheets
import "../../scss/knowledge-styles.scss";

// Vendors
import React from "react";
import ReactDOM from "react-dom";

// Our own libraries
import { onReady } from "@library/application";
import { registerReducer } from "@library/state/reducerRegistry";
import { debug } from "@library/utility";
import { getMeta } from "@library/application";
import NotificationsModel from "@library/notifications/NotificationsModel";
import ConversationsModel from "@library/conversations/ConversationsModel";

// Knowledge Modules
import rootReducer from "@knowledge/state/reducer";
import KnowledgeApp from "@knowledge/KnowledgeApp";
import { initAllUserContent } from "@library/user-content";

debug(getMeta("context.debug"));

onReady(() => {
    initAllUserContent();
    registerReducer("knowledge", rootReducer);
    registerReducer("notifications", new NotificationsModel().reducer);
    registerReducer("conversations", new ConversationsModel().reducer);
    const app = document.querySelector("#app");
    ReactDOM.render(<KnowledgeApp />, app);
});
