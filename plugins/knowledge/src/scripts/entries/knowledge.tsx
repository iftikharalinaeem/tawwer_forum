/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

// Import stylesheets
import "../../scss/knowledge-styles.scss";

// Vendors
import React from "react";

// Our own libraries
import apiv2 from "@library/apiv2";
import { onReady, t, formatUrl } from "@library/utility/appUtils";
import { debug } from "@vanilla/utils";
import { getMeta } from "@library/utility/appUtils";
import { initAllUserContent } from "@library/content";

// Knowledge Modules
import { deploymentKeyMiddleware } from "@knowledge/server/deploymentKeyMiddleware";
import KnowledgeApp from "@knowledge/KnowledgeApp";
import { Router } from "@library/Router";
import { getPageRoutes } from "@knowledge/routes/pageRoutes";
import { AppContext } from "@library/AppContext";
import { mountReact } from "@vanilla/react-utils";
import ErrorPage from "@knowledge/pages/ErrorPage";
import { serverReducer } from "@knowledge/server/serverReducer";
import { registerReducer } from "@library/redux/reducerRegistry";
import kbReducer from "@knowledge/state/reducer";
import Permission from "@library/features/users/Permission";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import DropDownItemLinkWithCount from "@library/flyouts/items/DropDownItemLinkWithCount";
import UserDropDownContents from "@library/headers/mebox/pieces/UserDropDownContents";
import { registerDefaultNavItem } from "@library/headers/navigationVariables";

debug(getMeta("context.debug"));

apiv2.interceptors.response.use(deploymentKeyMiddleware);
Router.addRoutes(getPageRoutes());

registerReducer("server", serverReducer);
registerReducer("knowledge", kbReducer);
const render = () => {
    const app = document.querySelector("#app") as HTMLElement;
    mountReact(
        <AppContext errorComponent={<ErrorPage />}>
            <KnowledgeApp />
        </AppContext>,
        app,
    );
};

UserDropDownContents.registerBeforeUserDropDown(props => {
    return (
        <Permission permission="articles.add">
            <DropDownSection title={t("Articles")}>
                <DropDownItemLinkWithCount
                    to="/kb/drafts"
                    name={t("Drafts")}
                    count={props.getCountByName("ArticleDrafts")}
                />
            </DropDownSection>
        </Permission>
    );
});

registerDefaultNavItem(() => {
    return {
        children: t("Help Menu", "Help"),
        permission: "kb.view",
        to: "/kb",
    };
});

onReady(() => {
    initAllUserContent();
    render();
});
