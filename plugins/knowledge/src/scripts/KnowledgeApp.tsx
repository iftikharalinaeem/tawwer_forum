/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import KnowledgeSearchProvider from "@knowledge/modules/search/KnowledgeSearchProvider";
import { NavHistoryContextProvider } from "@knowledge/navigation/NavHistoryContext";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import RouteActions from "@knowledge/routes/RouteActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import SearchContext from "@library/contexts/SearchContext";
import Loader from "@library/loaders/Loader";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import { Router } from "@library/Router";
import React, { useCallback, useDebugValue, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { BrowserRouter } from "react-router-dom";
import PageLoader from "@library/routing/PageLoader";
import { Backgrounds } from "@vanilla/library/src/scripts/layout/Backgrounds";
import { SearchPageRoute } from "@vanilla/library/src/scripts/search/SearchPageRoute";

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
                <KbErrorPage error={routeState.error} />
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
                <Backgrounds />
                <KbErrorPage defaultError={DefaultKbError.NO_KNOWLEDGE_BASE} />
            </BrowserRouter>
        );
    }

    return (
        <SiteNavProvider categoryRecordType={KbRecordType.CATEGORY}>
            <SearchContext.Provider value={{ searchOptionProvider: new KnowledgeSearchProvider() }}>
                <NavHistoryContextProvider>
                    <Backgrounds />
                    <PageLoader status={LoadStatus.SUCCESS}>{content}</PageLoader>
                </NavHistoryContextProvider>
            </SearchContext.Provider>
        </SiteNavProvider>
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

export default KnowledgeApp;
