/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonGlobalVariables } from "@library/forms/buttonStyles";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";
import { InputDropDownBlock } from "@library/forms/themeEditor/InputDropDownBlock";
import InputNumberBlock from "@library/forms/themeEditor/InputNumberBlock";
import ThemeBuilderSection from "@library/forms/themeEditor/ThemeBuilderSection";
import ThemeBuilderSectionGroup from "@library/forms/themeEditor/ThemeBuilderSectionGroup";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import ThemeBuilderTitle from "@library/forms/themeEditor/ThemeBuilderTitle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n/src";
import { Form, FormikProvider, useFormik } from "formik";
import React, { useEffect } from "react";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables } from "./themeEditorReducer";
import { ThemePresetDropDown } from "./ThemePresetDropDown";
import { ensureColorHelper } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { useIFrameCommunication } from "@themingapi/theme/IframeCommunicationContext";
import { useLastValue } from "@vanilla/react-utils";
import isEqual from "lodash/isEqual";

export interface IThemeBuilderForm {
    variables?: IThemeVariables;
}

interface IFormState {
    errors: Record<string, any>;
    [key: string]: any;
}

export default function ThemeBuilderForm(props: IThemeBuilderForm) {
    const classes = themeBuilderClasses();
    const global = globalVariables();
    const { updateAssets } = useThemeActions();
    const buttonGlobals = buttonGlobalVariables();
    const variables = props.variables ?? {};
    const { sendMessage } = useIFrameCommunication();

    function getVariableErrors(obj) {
        const result: any[] = [];
        function recursivelyFindError(o) {
            o &&
                Object.keys(o).forEach(function(key) {
                    if (typeof o[key] === "object") {
                        recursivelyFindError(o[key]);
                    } else {
                        if (o[key]) {
                            // Value exists if there's an error
                            result.push(o);
                        } else {
                            // Value is undefined if no error exists
                            result.pop();
                        }
                    }
                });
        }
        recursivelyFindError(obj);
        return result;
    }

    const form = useFormik({
        initialValues: { ...props.variables } as IFormState,
        onSubmit: () => {},
    });

    const { values } = form;
    const prevValues = useLastValue(values);

    useEffect(() => {
        if (!isEqual(prevValues, values)) {
            const errorVariables = getVariableErrors(values.errors);
            let hasError = errorVariables.length > 0;

            updateAssets({
                assets: {
                    variables: {
                        data: values,
                        type: "string",
                    },
                },
                errors: hasError,
            });

            if (!hasError) {
                sendMessage?.(values);
            }
        }
        // return values;
        // return values;
    }, [values, sendMessage, updateAssets, prevValues]);

    return (
        <FormikProvider value={form}>
            {/* The translate shouldn't be mandatory, it's a bug in this version of Formik */}
            <Form translate="yes" className={classes.root}>
                <ThemeBuilderTitle />
                <ThemePresetDropDown />
                <ColorPickerBlock
                    colorPicker={{
                        variableID: "global.mainColors.primary",
                        defaultValue: variables.global?.mainColors?.primary
                            ? ensureColorHelper(variables.global.mainColors.primary)
                            : global.mainColors.primary,
                    }}
                    inputBlock={{ label: t("Brand Color") }}
                />

                <ThemeBuilderSection label={"Body"}>
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.body.backgroundImage.color",
                            defaultValue: variables.global?.body?.backgroundImage?.color
                                ? ensureColorHelper(variables.global.body.backgroundImage.color)
                                : global.body.backgroundImage.color,
                        }}
                        inputBlock={{ label: t("Background Color") }}
                    />
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.mainColors.fg",
                            defaultValue: variables.global?.mainColors?.fg
                                ? ensureColorHelper(variables.global.mainColors.fg)
                                : global.mainColors.fg,
                        }}
                        inputBlock={{ label: t("Text") }}
                    />

                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.links.colors.default",
                            defaultValue: variables.global?.links?.colors?.default
                                ? ensureColorHelper(variables.global.links.colors.default)
                                : global.links.colors.default,
                        }}
                        inputBlock={{ label: t("Links") }}
                    />

                    <InputDropDownBlock
                        inputBlock={{
                            label: t("Font"),
                        }}
                        inputDropDown={{
                            variableID: "global.fonts.families.body",
                            options: [
                                {
                                    label: t("Open Sans"),
                                    value: "Open Sans",
                                },
                            ],
                            selectedIndex: 0,
                        }}
                    />
                </ThemeBuilderSection>
                <ThemeBuilderSection label={t("Buttons & Inputs")}>
                    <InputNumberBlock
                        inputNumber={{
                            variableID: "global.border.radius",
                            defaultValue: variables.border?.radius
                                ? variables.border.radius
                                : globalVariables().border.radius,
                        }}
                        inputBlock={{ label: t("Border Radius") }}
                    />

                    <ThemeBuilderSectionGroup label={t("Primary Buttons")}>
                        <InputDropDownBlock
                            inputBlock={{
                                label: t("Button Style"),
                            }}
                            inputDropDown={{
                                variableID: "button.primary.buttonPreset.style",
                                options: [
                                    {
                                        label: t("Solid"),
                                        value: "solid",
                                    },
                                    {
                                        label: t("Outline"),
                                        value: "outline",
                                    },
                                ],
                                selectedIndex: 0,
                            }}
                        />

                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primary",
                                defaultValue: variables.buttonGlobals?.colors?.primary
                                    ? ensureColorHelper(variables.buttonGlobals.colors.primary)
                                    : buttonGlobals.colors.primary,
                            }}
                            inputBlock={{ label: t("Background") }}
                        />

                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primaryContrast",
                                defaultValue: variables.buttonGlobals?.colors?.primaryContrast
                                    ? ensureColorHelper(variables.buttonGlobals.colors.primaryContrast)
                                    : buttonGlobals.colors.primaryContrast,
                            }}
                            inputBlock={{ label: t("Text") }}
                        />
                    </ThemeBuilderSectionGroup>

                    <ThemeBuilderSectionGroup label={t("Secondary Buttons")}>
                        <InputDropDownBlock
                            inputBlock={{
                                label: t("Button Style"),
                            }}
                            inputDropDown={{
                                variableID: "button.primary.buttonPreset.style",
                                options: [
                                    {
                                        label: t("Solid"),
                                        value: "solid",
                                    },
                                    {
                                        label: t("Outline"),
                                        value: "outline",
                                    },
                                ],
                                selectedIndex: 0,
                            }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.bg",
                                defaultValue: variables.buttonGlobals?.colors?.bg
                                    ? ensureColorHelper(variables.buttonGlobals.colors.bg)
                                    : buttonGlobals.colors.bg,
                            }}
                            inputBlock={{ label: t("Background") }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.fg",
                                defaultValue: variables.buttonGlobals?.colors?.fg
                                    ? ensureColorHelper(variables.buttonGlobals.colors.fg)
                                    : buttonGlobals.colors.fg,
                            }}
                            inputBlock={{ label: t("Text") }}
                        />
                    </ThemeBuilderSectionGroup>
                </ThemeBuilderSection>
            </Form>
        </FormikProvider>
    );
}
