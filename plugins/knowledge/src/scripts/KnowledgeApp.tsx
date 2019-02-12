/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Provider } from "react-redux";
import getStore from "@library/state/getStore";
import KnowledgeRoutes from "@knowledge/routes/KnowledgeRoutes";
import DeviceContext, { DeviceProvider } from "@library/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";
import { Route, BrowserRouter } from "react-router-dom";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import apiv2 from "@library/apiv2";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { formatUrl } from "@library/application";
import SearchContext from "@library/contexts/SearchContext";
import PagesContext from "@library/contexts/PagesContext";
import KnowledgeSearchProvider from "@knowledge/modules/search/KnowledgeSearchProvider";
import { SearchRoute } from "@knowledge/routes/pageRoutes";
import { ScrollOffsetProvider } from "@library/contexts/ScrollOffsetContext";
import SiteNavProvider from "@library/components/siteNav/SiteNavContext";
import { LinkContextProvider } from "@library/components/navigation/LinkContextProvider";

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
            </Provider>
        );
    }

    private get pages() {
        return {
            search: SearchRoute,
        };
    }
}
