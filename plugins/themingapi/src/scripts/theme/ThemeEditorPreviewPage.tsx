/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect, useState } from "react";
import { MemoryRouter, useParams } from "react-router-dom";
import TitleBar from "@library/headers/TitleBar";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import Container from "@library/layout/components/Container";
import Paragraph from "@vanilla/library/src/scripts/layout/Paragraph";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import Button from "@vanilla/library/src/scripts/forms/Button";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { LinkContext } from "@vanilla/library/src/scripts/routing/links/LinkContextProvider";
import themeEditorPreviewClasses from "./ThemeEditorPreviewContents.styles";
import {
    useOwnFrameMessages,
    useIFrameCommunication,
    IframeCommunicationContextProvider,
} from "@themingapi/theme/IframeCommunicationContext";
import { ThemeProvider } from "@vanilla/library/src/scripts/theming/ThemeProvider";
import NotFoundPage from "@vanilla/library/src/scripts/routing/NotFoundPage";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { useThemeCacheID } from "@vanilla/library/src/scripts/styles/styleUtils";
import { useThemeActions } from "@vanilla/library/src/scripts/theming/ThemeActions";
import { userContentClasses } from "@vanilla/library/src/scripts/content/userContentStyles";
import { ThemeEditorPreviewContents } from "@themingapi/theme/ThemeEditorPreviewContents";

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
