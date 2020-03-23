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
import React, { useState } from "react";
import actionCreatorFactory from "typescript-fsa";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables, useThemeEditorState } from "./themeEditorReducer";

export interface IThemeBuilderForm {
    variables?: IThemeVariables;
}

const createAction = actionCreatorFactory("@@themeBuilderPanel");
export const setActivePanelAC = createAction<{ panel: ActiveVariablePanel }>("setActivePanel");

export enum ActiveVariablePanel {
    GLOBAL = "global",
    TITLE_BAR = "titleBar",
    BANNER = "banner",
}

export default function ThemeBuilderPanel() {
    const classes = themeBuilderClasses();
    const { updateAssets } = useThemeActions();
    const { sendMessage } = useIFrameCommunication();
    const variables = useThemeEditorState().form?.assets.variables?.data;

    const [activePanel, setActivePanel] = useState(ActiveVariablePanel.GLOBAL);

    useOwnFrameMessages(e => {
        const { data } = e;
        if (e.data.type === setActivePanelAC.type) {
            setActivePanel(data.payload.panel);
        }
    });

    return (
        <ThemeBuilderContextProvider
            onChange={(newVariables, hasError) => {
                updateAssets({
                    assets: {
                        variables: {
                            data: newVariables,
                            type: "string",
                        },
                    },
                    errors: hasError,
                });

                if (!hasError) {
                    sendMessage?.(newVariables);
                }
            }}
            rawThemeVariables={variables}
        >
            <div className={classes.root}>
                {activePanel === ActiveVariablePanel.GLOBAL && <ThemeBuilderSectionGlobal />}
                {activePanel === ActiveVariablePanel.TITLE_BAR && <ThemeBuilderSectionTitleBar />}
                {activePanel === ActiveVariablePanel.BANNER && <ThemeBuilderSectionBanner />}
            </div>
        </ThemeBuilderContextProvider>
    );
}
