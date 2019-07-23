/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeSearchProvider from "@knowledge/modules/search/KnowledgeSearchProvider";
import ErrorPage from "@knowledge/pages/ErrorPage";
import KnowledgeRoutes from "@knowledge/routes/KnowledgeRoutes";
import { SearchRoute } from "@knowledge/routes/pageRoutes";
import { IStoreState } from "@knowledge/state/model";
import SearchContext from "@library/contexts/SearchContext";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import getStore from "@library/redux/getStore";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import PagesContext from "@library/routing/PagesContext";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { formatUrl, getMeta } from "@library/utility/appUtils";
import React, { useMemo, useEffect } from "react";
import { Provider } from "react-redux";
import { Router, Route } from "react-router-dom";
import { LiveAnnouncer } from "react-aria-live";
import { hot } from "react-hot-loader";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { NavHistoryContextProvider } from "@knowledge/navigation/NavHistoryContext";
import { FontSizeCalculatorProvider } from "@library/layout/pageHeadingContext";
import { createBrowserHistory } from "history";
import { initPageViewTracking } from "@library/pageViews/pageViewTracking";

/*
 * Top level application component for knowledge.
 * This is made to mounted with ReactDOM.
 */
function KnowledgeApp() {
    const store = useMemo(() => getStore<IStoreState>(), []);
    const history = useMemo(() => createBrowserHistory({ basename: formatUrl("") }), []);

    return (
        <Provider store={store}>
            <LiveAnnouncer>
                <ThemeProvider errorComponent={<ErrorPage />} themeKey={getMeta("ui.themeKey", "keystone")}>
                    <PagesContext.Provider
                        value={{
                            pages: {
                                search: SearchRoute,
                            },
                        }}
                    >
                        <ScrollOffsetProvider scrollWatchingEnabled={false}>
                            <SiteNavProvider categoryRecordType={KbRecordType.CATEGORY}>
                                <SearchContext.Provider value={{ searchOptionProvider: new KnowledgeSearchProvider() }}>
                                    <NavHistoryContextProvider>
                                        <DeviceProvider>
                                            <Router history={history}>
                                                <LinkContextProvider linkContext={formatUrl("/kb", true)}>
                                                    <FontSizeCalculatorProvider>
                                                        <Route component={KnowledgeRoutes} />
                                                    </FontSizeCalculatorProvider>
                                                </LinkContextProvider>
                                            </Router>
                                        </DeviceProvider>
                                    </NavHistoryContextProvider>
                                </SearchContext.Provider>
                            </SiteNavProvider>
                        </ScrollOffsetProvider>
                    </PagesContext.Provider>
                </ThemeProvider>
            </LiveAnnouncer>
        </Provider>
    );
}

export default hot(module)(KnowledgeApp);
