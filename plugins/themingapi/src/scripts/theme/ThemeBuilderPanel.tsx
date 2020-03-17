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
import React, { useEffect, useCallback } from "react";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables, useThemeEditorState } from "./themeEditorReducer";
import { ThemePresetDropDown } from "./ThemePresetDropDown";
import { ensureColorHelper } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { useIFrameCommunication } from "@themingapi/theme/IframeCommunicationContext";
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

export interface IThemeBuilderForm {
    variables?: IThemeVariables;
}

interface IFormState {
    errors: Record<string, any>;
    [key: string]: any;
}

const buttonPresetOptions = [
    {
        label: t("Solid"),
        value: "solid",
    },
    {
        label: t("Outline"),
        value: "outline",
    },
];

export default function ThemeBuilderForm(props: IThemeBuilderForm) {
    const classes = themeBuilderClasses();
    const { updateAssets } = useThemeActions();
    const { sendMessage } = useIFrameCommunication();
    const variables = useThemeEditorState().form?.assets.variables?.data;

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
            {/* The translate shouldn't be mandatory, it's a bug in this version of Formik */}
            <div className={classes.root}>
                <ThemeBuilderTitle />
                <ThemeBuilderBlock label={t("Preset")}>
                    <ThemeDropDown
                        // This is actually an array, but the first is the real one. The rest are fallbacks.
                        variableKey="global.options.preset"
                        options={[
                            { label: t("Light"), value: GlobalPreset.LIGHT },
                            { label: t("Dark"), value: GlobalPreset.DARK },
                        ]}
                    ></ThemeDropDown>
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Brand Color")}>
                    <ThemeColorPicker variableKey={"global.mainColors.primary"} />
                </ThemeBuilderBlock>
                <ThemeBuilderSection label={t("Body")}>
                    <ThemeBuilderBlock label={t("Background")}>
                        <ThemeColorPicker variableKey="global.mainColors.bg" />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Text")}>
                        <ThemeColorPicker variableKey="global.mainColors.fg" />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Links")}>
                        <ThemeColorPicker variableKey="global.links.colors.default" />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Font")}>
                        <GoogleFontDropdown />
                    </ThemeBuilderBlock>
                </ThemeBuilderSection>
                <ThemeBuilderSection label={t("Buttons & Inputs")}>
                    <ThemeBuilderBlock label={t("Border Radius")}>
                        <ThemeInputNumber variableKey="global.border.radius" />
                    </ThemeBuilderBlock>
                    <ThemeBuilderButtonSection label={t("Primary Buttons")} buttonType="primary" />
                    <ThemeBuilderButtonSection label={t("Secondary Buttons")} buttonType="standard" />
                </ThemeBuilderSection>
            </div>
        </ThemeBuilderContextProvider>
    );
}
