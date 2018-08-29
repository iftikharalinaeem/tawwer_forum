/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { BrowserRouter, Route } from "react-router-dom";
import { Provider } from "react-redux";
import getStore from "@dashboard/state/getStore";
import KnowledgeRoutes from "@knowledge/KnowledgeRoutes";

export default function KnowledgeApp() {
    const store = getStore();
    return (
        <Provider store={store}>
            <KnowledgeRoutes />
        </Provider>
    );
}
