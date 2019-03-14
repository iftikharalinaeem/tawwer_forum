/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import KnowledgeRoutes from "@knowledge/routes/KnowledgeRoutes";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { Devices } from "@library/layout/DeviceChecker";
import { Route, BrowserRouter } from "react-router-dom";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { formatUrl, getMeta } from "@library/dom/appUtils";
import SearchContext from "@library/contexts/SearchContext";
import PagesContext from "@library/routing/PagesContext";
import KnowledgeSearchProvider from "@knowledge/modules/search/KnowledgeSearchProvider";
import { SearchRoute } from "@knowledge/routes/pageRoutes";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import ErrorPage from "@knowledge/routes/ErrorPage";

/*
 * Top level application component for knowledge.
 * This is made to mounted with ReactDOM.
 */
export default class KnowledgeApp extends React.Component {
    private store = getStore<IStoreState>();

    /**
     * Device checker detects device and calls a force update if needed to update the current device.
     */
    public render() {
        return (
            <Provider store={this.store}>
                <ThemeProvider errorComponent={<ErrorPage />} themeKey={getMeta("ui.themeKey", "keystone")}>
                    <PagesContext.Provider value={{ pages: this.pages }}>
                        <ScrollOffsetProvider scrollWatchingEnabled={true}>
                            <SiteNavProvider>
                                <SearchContext.Provider value={{ searchOptionProvider: new KnowledgeSearchProvider() }}>
                                    <DeviceProvider>
                                        <BrowserRouter>
                                            <LinkContextProvider linkContext={formatUrl("/kb", true)}>
                                                <Route component={KnowledgeRoutes} />
                                            </LinkContextProvider>
                                        </BrowserRouter>
                                    </DeviceProvider>{" "}
                                </SearchContext.Provider>
                            </SiteNavProvider>
                        </ScrollOffsetProvider>
                    </PagesContext.Provider>
                </ThemeProvider>
            </Provider>
        );
    }

    private get pages() {
        return {
            search: SearchRoute,
        };
    }
}
