/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonGlobalVariables, ButtonPreset } from "@library/forms/buttonStyles";
import { themeBuilderClasses } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilder.styles";
import { globalVariables, GlobalPreset } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n/src";
import { Form, FormikProvider, useFormik } from "formik";
import React, { useEffect, useCallback, useState } from "react";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables, useThemeEditorState } from "./themeEditorReducer";
import { ThemePresetDropDown } from "./ThemePresetDropDown";
import { ensureColorHelper } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { useIFrameCommunication, useOwnFrameMessages } from "@themingapi/theme/IframeCommunicationContext";
import { useLastValue } from "@vanilla/react-utils";
import isEqual from "lodash/isEqual";
import { ThemeBuilderTitle } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderTitle";
import { ThemeBuilderSection } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSection";
import { ThemeBuilderSectionGroup } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSectionGroup";
import { ThemeBuilderContextProvider } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import { ThemeBuilderBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBlock";
import { ThemeColorPicker } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeColorPicker";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { ThemeBuilderButtonSection } from "@themingapi/theme/builderSections/ThemeBuilderButtonSection";
import { ThemeInputNumber } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeInputNumber";
import { GoogleFontDropdown } from "@themingapi/theme/GoogleFontDropdown";
import { ThemeBuilderSectionGlobal } from "@themingapi/theme/builderSections/ThemeBuilderSectionGlobal";
import { ThemeBuilderSectionTitleBar } from "@themingapi/theme/builderSections/ThemeBuilderSectionTitleBar";
import { ThemeBuilderSectionBanner } from "@themingapi/theme/builderSections/ThemeBuilderSectionBanner";
import actionCreatorFactory from "typescript-fsa";

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
