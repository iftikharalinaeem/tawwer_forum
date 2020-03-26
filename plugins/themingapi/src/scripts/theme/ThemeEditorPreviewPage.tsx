/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IframeCommunicationContextProvider, useOwnFrameMessages } from "@themingapi/theme/IframeCommunicationContext";
import { ThemeEditorPreviewContents } from "@themingapi/theme/ThemeEditorPreviewContents";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { LinkContext } from "@vanilla/library/src/scripts/routing/links/LinkContextProvider";
import NotFoundPage from "@vanilla/library/src/scripts/routing/NotFoundPage";
import { useThemeCacheID, resetThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import { useThemeActions } from "@vanilla/library/src/scripts/theming/ThemeActions";
import { ThemeProvider } from "@vanilla/library/src/scripts/theming/ThemeProvider";
import React, { useCallback, useEffect, useState } from "react";
import { MemoryRouter, useParams } from "react-router-dom";
import themeEditorPreviewClasses from "./ThemeEditorPreviewContents.styles";
import { enableComponentTheming } from "@vanilla/library/src/scripts/utility/componentRegistry";

// force theming, even though this is a "dashboard" page.
enableComponentTheming();
resetThemeCache();
export default function ThemeEditorPreviewPage() {
    const [intialInputValue, newInputValue] = useState("Text Input");
    const classes = themeEditorPreviewClasses();
    const { id } = useParams();
    const { cacheID } = useThemeCacheID();
    const { forceVariables } = useThemeActions();

    useEffect(() => {
        document.body.classList.add(classes.contentContainer);
    });

    const onFrame = useCallback(
        (messageEvent: MessageEvent) => {
            forceVariables(messageEvent.data);
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
                        <ThemeEditorPreviewContents />
                    </IframeCommunicationContextProvider>
                </LinkContext.Provider>
            </MemoryRouter>
        </ThemeProvider>
    );
}
