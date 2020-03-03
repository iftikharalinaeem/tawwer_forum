/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";
import InputNumberBlock from "@library/forms/themeEditor/InputNumberBlock";
import ThemeBuilderSection from "@library/forms/themeEditor/ThemeBuilderSection";
import ThemeBuilderSectionGroup from "@library/forms/themeEditor/ThemeBuilderSectionGroup";
import ThemeBuilderTitle from "@library/forms/themeEditor/ThemeBuilderTitle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n/src";
import { ensureColorHelper } from "@vanilla/library/src/scripts/forms/themeEditor/ColorPicker";
import { Form, FormikProvider, useFormik } from "formik";
import React from "react";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables } from "./themeEditorReducer";
import { buttonGlobalVariables } from "@library/forms/buttonStyles";
import { InputDropDownBlock } from "@library/forms/themeEditor/InputDropDownBlock";
import { ThemePresetDropDown } from "./ThemePresetDropDown";
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
        const result = [];
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

            const val = { ...data, ...form.values };
            updateAssets({
                assets: {
                    variables: {
                        data: JSON.parse(JSON.stringify(val)),
                        type: "string",
                    },
                },
                errors: errorVariables.length > 0 ? true : false,
            });
        },
    });
    //console.log("data-->", props.variables);
    console.log("form-->", form.values);

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
                {form.values.errors?.global?.mainColors?.primary && (
                    <div className={classes.colorErrorMessage}>{form.values.errors.global.mainColors.primary}</div>
                )}

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
                    {form.values.errors?.global?.body?.backgroundImage?.color && (
                        <div className={classes.colorErrorMessage}>
                            {form.values.errors.global.body.backgroundImage.color}
                        </div>
                    )}
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.mainColors.fg",
                            defaultValue: data.global?.mainColors?.fg
                                ? ensureColorHelper(data.global.mainColors.fg)
                                : global.mainColors.fg,
                        }}
                        inputBlock={{ label: t("Text") }}
                    />
                    {form.values.errors?.global?.mainColors?.fg && (
                        <div className={classes.colorErrorMessage}>{form.values.errors.global.mainColors.fg}</div>
                    )}

                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.links.colors.default",
                            defaultValue: data.global?.links?.colors?.default
                                ? ensureColorHelper(data.global.links.colors.default)
                                : global.links.colors.default,
                        }}
                        inputBlock={{ label: t("Links") }}
                    />
                    {form.values.errors?.global?.links?.colors?.default && (
                        <div className={classes.colorErrorMessage}>
                            {form.values.errors.global.links.colors.default}
                        </div>
                    )}

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
                        {form.values.errors?.buttonGlobals?.colors?.primary && (
                            <div className={classes.colorErrorMessage}>
                                {form.values.errors.buttonGlobals.colors.primary}
                            </div>
                        )}
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primaryContrast",
                                defaultValue: data.buttonGlobals?.colors?.primaryContrast
                                    ? ensureColorHelper(data.buttonGlobals.colors.primaryContrast)
                                    : buttonGlobals.colors.primaryContrast,
                            }}
                            inputBlock={{ label: t("Text") }}
                        />
                        {form.values.errors?.buttonGlobals?.colors?.primaryContrast && (
                            <div className={classes.colorErrorMessage}>
                                {form.values.errors.buttonGlobals.colors.primaryContrast}
                            </div>
                        )}
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
                        {form.values.errors?.buttonGlobals?.colors?.bg && (
                            <div className={classes.colorErrorMessage}>
                                {form.values.errors.buttonGlobals.colors.bg}
                            </div>
                        )}
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.fg",
                                defaultValue: data.buttonGlobals?.colors?.fg
                                    ? ensureColorHelper(data.buttonGlobals.colors.fg)
                                    : buttonGlobals.colors.fg,
                            }}
                            inputBlock={{ label: t("Text") }}
                        />
                        {form.values.errors?.buttonGlobals?.colors?.fg && (
                            <div className={classes.colorErrorMessage}>
                                {form.values.errors.buttonGlobals.colors.fg}
                            </div>
                        )}
                    </ThemeBuilderSectionGroup>
                </ThemeBuilderSection>
            </Form>
        </FormikProvider>
    );
}
