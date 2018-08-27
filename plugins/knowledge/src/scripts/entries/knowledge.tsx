/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import React from "react";
import ReactDOM from "react-dom";
import HelloKnowledge from "@knowledge/components/HelloKnowledge";
import { onReady } from "@dashboard/application";

// Import stylesheets
import "../../scss/knowledge-styles.scss";

onReady(() => {
    const app = document.querySelector("#app");
    ReactDOM.render(<HelloKnowledge />, app);
});
