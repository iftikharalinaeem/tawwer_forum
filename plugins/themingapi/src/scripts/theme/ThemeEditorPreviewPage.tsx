/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IframeCommunicationContextProvider, useOwnFrameMessages } from "@themingapi/theme/IframeCommunicationContext";
import { ThemeEditorPreviewContentsGlobal } from "@themingapi/theme/ThemeEditorPreviewContentsGlobal";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { LinkContext } from "@vanilla/library/src/scripts/routing/links/LinkContextProvider";
import NotFoundPage from "@vanilla/library/src/scripts/routing/NotFoundPage";
import { useThemeCacheID, resetThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import ThemeActions, { useThemeActions } from "@vanilla/library/src/scripts/theming/ThemeActions";
import { ThemeProvider } from "@vanilla/library/src/scripts/theming/ThemeProvider";
import React, { useCallback, useEffect, useState } from "react";
import { MemoryRouter, useParams } from "react-router-dom";
import themeEditorPreviewClasses from "./ThemeEditorPreviewContents.styles";
import { enableComponentTheming } from "@vanilla/library/src/scripts/utility/componentRegistry";
import {
    ActivePanelContextProvider,
    useActivePanelContext,
    ActiveVariablePanel,
} from "@themingapi/theme/ActivePanelContext";
import { ThemeEditorPreviewContentsContent } from "@themingapi/theme/ThemeEditorPreviewContentsContent";

// force theming, even though this is a "dashboard" page.
enableComponentTheming();
export default function ThemeEditorPreviewPage() {
    const classes = themeEditorPreviewClasses();
    const { id } = useParams();
    const { cacheID } = useThemeCacheID();
    const { forceVariables } = useThemeActions();

    useEffect(() => {
        document.body.classList.add(classes.contentContainer);
    });

    const onFrame = useCallback(
        (messageEvent: MessageEvent) => {
            if (messageEvent.data.type === ThemeActions.forceVariablesAC.type) {
                forceVariables(messageEvent.data.payload);
            }
        },
        [forceVariables],
    );

    useOwnFrameMessages(onFrame);

    if (!id) {
        return <NotFoundPage />;
    }

    return (
        <ThemeProvider themeKey={id} key={cacheID} errorComponent={ErrorPage}>
            <MemoryRouter>
                <LinkContext.Provider
                    value={{
                        linkContext: "",
                        isDynamicNavigation: () => {
                            return true;
                        },
                        pushSmartLocation: () => {},
                        makeHref: () => {
                            return "";
                        },
                    }}
                >
                    <IframeCommunicationContextProvider>
                        <ActivePanelContextProvider isPreview>
                            <ThemePreviewPages />
                        </ActivePanelContextProvider>
                    </IframeCommunicationContextProvider>
                </LinkContext.Provider>
            </MemoryRouter>
        </ThemeProvider>
    );
}

function ThemePreviewPages() {
    const { activePanel } = useActivePanelContext();

    switch (activePanel) {
        case ActiveVariablePanel.BANNER:
        case ActiveVariablePanel.GLOBAL:
        case ActiveVariablePanel.TITLE_BAR:
            return <ThemeEditorPreviewContentsGlobal />;
        case ActiveVariablePanel.CONTENT_BANNER:
            return <ThemeEditorPreviewContentsContent />;
        default:
            return <ThemeEditorPreviewContentsGlobal />;
    }
}
