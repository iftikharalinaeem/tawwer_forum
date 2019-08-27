/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeSearchProvider from "@knowledge/modules/search/KnowledgeSearchProvider";
import { NavHistoryContextProvider } from "@knowledge/navigation/NavHistoryContext";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { SearchRoute } from "@knowledge/routes/pageRoutes";
import { Application } from "@library/Application";
import SearchContext from "@library/contexts/SearchContext";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import PagesContext from "@library/routing/PagesContext";
import React from "react";
import { Router } from "@library/Router";
import { hot } from "react-hot-loader";

/*
 * Top level application component for knowledge.
 * This is made to mounted with ReactDOM.
 */
function KnowledgeApp() {
    return (
        <Application>
            <PagesContext.Provider
                value={{
                    pages: {
                        search: SearchRoute,
                    },
                }}
            >
                <SiteNavProvider categoryRecordType={KbRecordType.CATEGORY}>
                    <SearchContext.Provider value={{ searchOptionProvider: new KnowledgeSearchProvider() }}>
                        <NavHistoryContextProvider>
                            <Router sectionRoot="/kb" />
                        </NavHistoryContextProvider>
                    </SearchContext.Provider>
                </SiteNavProvider>
            </PagesContext.Provider>
        </Application>
    );
}

export default hot(module)(KnowledgeApp);
