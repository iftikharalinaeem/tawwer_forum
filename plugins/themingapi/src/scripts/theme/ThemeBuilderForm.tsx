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
import React from "react";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables } from "./themeEditorReducer";
import { ThemePresetDropDown } from "./ThemePresetDropDown";
import { ensureColorHelper } from "@vanilla/library/src/scripts/styles/styleHelpers";

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
    const dataString = JSON.stringify(props.variables);
    const data = JSON.parse(dataString).data;

    function getVariableErrors(obj) {
        const result: any[] = [];
        function recursivelyFindError(o) {
            Object.keys(o).forEach(function(key) {
                if (typeof o[key] === "object") {
                    recursivelyFindError(o[key]);
                } else {
                    if (o[key] === "Invalid Color") {
                        result.push(o);
                    } else {
                        result.pop();
                    }
                }
            });
        }
        recursivelyFindError(obj);
        return result;
    }

    const form = useFormik({
        initialValues: {} as IFormState,
        onSubmit: () => {},
        validate: values => {
            const errorVariables = getVariableErrors(values.errors);

            let hasError = errorVariables.length > 0;

            const val = { ...data, ...form.values };

            const payload = {
                variables: {
                    data: JSON.parse(JSON.stringify(val)),
                    type: "string",
                },
            };

            updateAssets({
                assets: payload,
                errors: hasError,
            });
        },
    });

    return (
        <FormikProvider value={form}>
            {/* The translate shouldn't be mandatory, it's a bug in this version of Formik */}
            <Form translate="yes" className={classes.root}>
                <ThemeBuilderTitle />
                <ThemePresetDropDown />
                <ColorPickerBlock
                    colorPicker={{
                        variableID: "global.mainColors.primary",
                        defaultValue: data.global?.mainColors?.primary
                            ? ensureColorHelper(data.global.mainColors.primary)
                            : global.mainColors.primary,
                    }}
                    inputBlock={{ label: t("Brand Color") }}
                />

                <ThemeBuilderSection label={"Body"}>
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.body.backgroundImage.color",
                            defaultValue: data.global?.body?.backgroundImage?.color
                                ? ensureColorHelper(data.global.body.backgroundImage.color)
                                : global.body.backgroundImage.color,
                        }}
                        inputBlock={{ label: t("Background Color") }}
                    />
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.mainColors.fg",
                            defaultValue: data.global?.mainColors?.fg
                                ? ensureColorHelper(data.global.mainColors.fg)
                                : global.mainColors.fg,
                        }}
                        inputBlock={{ label: t("Text") }}
                    />

                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.links.colors.default",
                            defaultValue: data.global?.links?.colors?.default
                                ? ensureColorHelper(data.global.links.colors.default)
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
                            defaultValue: data.border?.radius ? data.border.radius : globalVariables().border.radius,
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
                                defaultValue: data.buttonGlobals?.colors?.primary
                                    ? ensureColorHelper(data.buttonGlobals.colors.primary)
                                    : buttonGlobals.colors.primary,
                            }}
                            inputBlock={{ label: t("Background") }}
                        />

                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primaryContrast",
                                defaultValue: data.buttonGlobals?.colors?.primaryContrast
                                    ? ensureColorHelper(data.buttonGlobals.colors.primaryContrast)
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
                                defaultValue: data.buttonGlobals?.colors?.bg
                                    ? ensureColorHelper(data.buttonGlobals.colors.bg)
                                    : buttonGlobals.colors.bg,
                            }}
                            inputBlock={{ label: t("Background") }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.fg",
                                defaultValue: data.buttonGlobals?.colors?.fg
                                    ? ensureColorHelper(data.buttonGlobals.colors.fg)
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
