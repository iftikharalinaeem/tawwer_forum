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
import React from "react";
import { Provider } from "react-redux";
import { BrowserRouter, Route } from "react-router-dom";
import { LiveAnnouncer } from "react-aria-live";
import { hot } from "react-hot-loader";

interface IState {
    app: React.ReactNode;
}

/*
 * Top level application component for knowledge.
 * This is made to mounted with ReactDOM.
 */
class KnowledgeApp extends React.Component<{}, IState> {
    private store = getStore<IStoreState>();

    /**
     * Device checker detects device and calls a force update if needed to update the current device.
     */
    public render() {
        return (
            <Provider store={this.store}>
                <LiveAnnouncer>
                    <ThemeProvider errorComponent={<ErrorPage />} themeKey={getMeta("ui.themeKey", "keystone")}>
                        <PagesContext.Provider value={{ pages: this.pages }}>
                            <ScrollOffsetProvider scrollWatchingEnabled={true}>
                                <SiteNavProvider>
                                    <SearchContext.Provider
                                        value={{ searchOptionProvider: new KnowledgeSearchProvider() }}
                                    >
                                        <DeviceProvider>
                                            <BrowserRouter basename={formatUrl("")}>
                                                <LinkContextProvider linkContext={formatUrl("/kb", true)}>
                                                    <Route component={KnowledgeRoutes} />
                                                </LinkContextProvider>
                                            </BrowserRouter>
                                        </DeviceProvider>
                                    </SearchContext.Provider>
                                </SiteNavProvider>
                            </ScrollOffsetProvider>
                        </PagesContext.Provider>
                    </ThemeProvider>
                </LiveAnnouncer>
            </Provider>
        );
    }

    private get pages() {
        return {
            search: SearchRoute,
        };
    }
}

export default hot(module)(KnowledgeApp);
