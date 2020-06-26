/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ThemeBuilderSectionBanner } from "@themingapi/theme/builderSections/ThemeBuilderSectionBanner";
import { ThemeBuilderSectionGlobal } from "@themingapi/theme/builderSections/ThemeBuilderSectionGlobal";
import { ThemeBuilderSectionTitleBar } from "@themingapi/theme/builderSections/ThemeBuilderSectionTitleBar";
import { useIFrameCommunication, useOwnFrameMessages } from "@themingapi/theme/IframeCommunicationContext";
import { themeBuilderClasses } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilder.styles";
import { ThemeBuilderContextProvider } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import React from "react";
import { useThemeEditorActions } from "./ThemeEditorActions";
import { useThemeEditorState } from "./themeEditorReducer";
import { IThemeVariables } from "@vanilla/library/src/scripts/theming/themeReducer";
import {
    ActivePanelContextProvider,
    ActiveVariablePanel,
    useActivePanelContext,
} from "@themingapi/theme/ActivePanelContext";
import { ThemeBuilderSectionContentBanner } from "@themingapi/theme/builderSections/ThemeBuilderSectionContentBanner";
import { ErrorBoundary } from "@vanilla/library/src/scripts/errorPages/ErrorBoundary";
import ThemeActions from "@vanilla/library/src/scripts/theming/ThemeActions";

export interface IThemeBuilderForm {
    variables?: IThemeVariables;
}

export default function ThemeBuilderPanel() {
    const classes = themeBuilderClasses();
    const { updateAssets } = useThemeEditorActions();
    const { sendMessage } = useIFrameCommunication();
    const variables = useThemeEditorState()?.form?.assets.variables?.data;
    const initialLoad = useThemeEditorState()?.form.initialLoad;

    return (
        <ErrorBoundary>
            <ThemeBuilderContextProvider
                onChange={(newVariables, hasError) => {
                    updateAssets({
                        assets: {
                            variables: {
                                data: newVariables,
                                type: "json",
                            },
                        },
                        edited: !initialLoad,
                        errors: hasError,
                    });

                    if (!hasError) {
                        const forceVariablesAC = ThemeActions.forceVariablesAC(newVariables);
                        sendMessage?.(forceVariablesAC);
                    }
                }}
                rawThemeVariables={variables ?? {}}
            >
                <div className={classes.root}>
                    <ActivePanelContextProvider>
                        <PanelItems />
                    </ActivePanelContextProvider>
                </div>
            </ThemeBuilderContextProvider>
        </ErrorBoundary>
    );
}

function PanelItems() {
    const { activePanel } = useActivePanelContext();
    switch (activePanel) {
        case ActiveVariablePanel.TITLE_BAR:
            return <ThemeBuilderSectionTitleBar />;
        case ActiveVariablePanel.BANNER:
            return <ThemeBuilderSectionBanner />;
        case ActiveVariablePanel.CONTENT_BANNER:
            return <ThemeBuilderSectionContentBanner />;
        case ActiveVariablePanel.GLOBAL:
        default:
            return <ThemeBuilderSectionGlobal />;
    }
}
