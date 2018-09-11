/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

// Import stylesheets
import "../../scss/knowledge-styles.scss";

// Vendors
import React from "react";
import ReactDOM from "react-dom";

// Our own libraries
import { onReady } from "@library/application";
import { registerReducer } from "@library/state/reducerRegistry";

// Knowledge Modules
import rootReducer from "@knowledge/rootReducer";
import KnowledgeApp from "@knowledge/KnowledgeApp";

onReady(() => {
    registerReducer("knowledge", rootReducer);
    const app = document.querySelector("#app");
    ReactDOM.render(<KnowledgeApp />, app);
});
