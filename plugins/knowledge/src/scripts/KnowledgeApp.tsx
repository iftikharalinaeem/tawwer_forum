/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { DefaultError } from "@knowledge/modules/common/PageErrorMessage";
import KnowledgeSearchProvider from "@knowledge/modules/search/KnowledgeSearchProvider";
import { NavHistoryContextProvider } from "@knowledge/navigation/NavHistoryContext";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import ErrorPage from "@knowledge/pages/ErrorPage";
import { SearchRoute } from "@knowledge/routes/pageRoutes";
import RouteActions from "@knowledge/routes/RouteActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import SearchContext from "@library/contexts/SearchContext";
import Loader from "@library/loaders/Loader";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import { Router } from "@library/Router";
import PagesContext from "@library/routing/PagesContext";
import React, { useCallback, useEffect, useDebugValue } from "react";
import { hot } from "react-hot-loader";
import { useDispatch, useSelector } from "react-redux";
import { BrowserRouter } from "react-router-dom";
import PageLoader from "@library/routing/PageLoader";

/*
 * Top level application component for knowledge.
 * This is made to mounted with ReactDOM.
 */
function KnowledgeApp() {
    const { kbLoadable, routeState, clearError, requestKnowledgeBases } = useConnect();

    useEffect(() => {
        if (kbLoadable.status === LoadStatus.PENDING) {
            requestKnowledgeBases();
        }
    });

    let content = <Router sectionRoot="/kb" onRouteChange={clearError} />;

    if (routeState.error) {
        content = (
            <BrowserRouter>
                <ErrorPage error={routeState.error} />
            </BrowserRouter>
        );
    } else if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(kbLoadable.status)) {
        content = (
            <BrowserRouter>
                <Loader />
            </BrowserRouter>
        );
    } else if (
        kbLoadable.status === LoadStatus.SUCCESS &&
        kbLoadable.data &&
        Object.values(kbLoadable.data).length === 0
    ) {
        content = (
            <BrowserRouter>
                <ErrorPage defaultError={DefaultError.NO_KNOWLEDGE_BASE} />
            </BrowserRouter>
        );
    }

    return (
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
                        <PageLoader status={kbLoadable.status}>{content}</PageLoader>
                    </NavHistoryContextProvider>
                </SearchContext.Provider>
            </SiteNavProvider>
        </PagesContext.Provider>
    );
}

function useConnect() {
    const kbLoadable = useSelector(
        (state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases.knowledgeBasesByID,
    );
    const routeState = useSelector((state: IKnowledgeAppStoreState) => state.knowledge.route);
    const dispatch = useDispatch();
    const clearError = useCallback(() => dispatch(RouteActions.resetAC), [dispatch]);
    const kbActions = useKnowledgeBaseActions();

    const result = {
        kbLoadable,
        routeState,
        clearError,
        requestKnowledgeBases: kbActions.getAll,
    };
    useDebugValue(result);
    return result;
}

export default hot(module)(KnowledgeApp);
