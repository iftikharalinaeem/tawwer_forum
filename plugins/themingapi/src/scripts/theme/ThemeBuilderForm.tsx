/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonGlobalVariables } from "@library/forms/buttonStyles";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";
import ThemeBuilderSection from "@library/forms/themeEditor/ThemeBuilderSection";
import ThemeBuilderSectionGroup from "@library/forms/themeEditor/ThemeBuilderSectionGroup";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import ThemeBuilderTitle from "@library/forms/themeEditor/ThemeBuilderTitle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n/src";
import { ensureColorHelper } from "@vanilla/library/src/scripts/forms/themeEditor/ColorPicker";
import { stringIsValidColor } from "@vanilla/library/src/scripts/styles/styleUtils";
import { formatUrl } from "@vanilla/library/src/scripts/utility/appUtils";
import { Form, FormikProvider, useFormik } from "formik";
import React from "react";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables } from "./themeEditorReducer";
import InputNumberBlock from "@library/forms/themeEditor/InputNumberBlock";

export interface IThemeBuilderForm {
    variables?: IThemeVariables;
}

export default function ThemeBuilderForm(props: IThemeBuilderForm) {
    const classes = themeBuilderClasses();
    const global = globalVariables();
    const { updateAssets, saveTheme } = useThemeActions();
    const buttonGlobals = buttonGlobalVariables();
    const dataString = JSON.stringify(props.variables);
    const data = JSON.parse(dataString).data;
    console.log("data==>", data);

    /*const validate = values => {
        const errors = {};

        if (!stringIsValidColor(values.global.body.backgroundImage.color)) {
            errors["backgroundImage"] = "Invalid Color";
        }
        return errors;
    };*/
    const form = useFormik({
        initialValues: {
            // primaryColor: data.global.mainColors.primary ? data.global.mainColors.primary : global.mainColors.primary,
            // backgroundImage: data.global.body
            //     ? data.global.body.backgroundImage.color
            //     : global.body.backgroundImage.color,
            // mainFg: data.global.mainColors ? data.global.mainColors.fg : global.mainColors.fg,
            // linkDefaultColor: data.global.links ? data.global.links.colors.default : global.links.colors.default,
            // buttonGlobalPrimary: data.buttonGlobals ? data.buttonGlobals.colors.primary : buttonGlobals.colors.primary,
            // buttonPrimaryContrast: data.buttonGlobals
            //     ? data.buttonGlobals.colors.primaryContrast
            //     : buttonGlobals.colors.primaryContrast,
            // buttonGlobalBg: data.buttonGlobals ? data.buttonGlobals.colors.bg : buttonGlobals.colors.bg,
            // buttonGlobalFg: data.buttonGlobals ? data.buttonGlobals.colors.fg : buttonGlobals.colors.fg,
        },
        // validate,
        onSubmit: async values => {
            //await saveTheme();
            // window.location.href = formatUrl("/theme/theme-settings", true);
        },
    });

    return (
        <FormikProvider value={form}>
            {/* The translate shouldn't be mandatory, it's a bug in this version of Formik */}
            <Form translate="yes" className={classes.root}>
                <ThemeBuilderTitle />

                <ColorPickerBlock
                    colorPicker={{
                        variableID: "global.mainColors.primary",
                        defaultValue: data.global.mainColors.primary
                            ? ensureColorHelper(data.global.mainColors.primary)
                            : global.mainColors.primary,
                        handleChange: () => {
                            const val = { ...data, ...form.values };
                            updateAssets({
                                assets: {
                                    variables: {
                                        data: JSON.parse(JSON.stringify(val)),
                                        type: "string",
                                    },
                                },
                            });
                        },
                    }}
                    inputBlock={{ label: t("Brand Color") }}
                />

                <ThemeBuilderSection label={"Body"}>
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.body.backgroundImage.color",
                            defaultValue: data.global.body
                                ? ensureColorHelper(data.global.body.backgroundImage.color)
                                : global.body.backgroundImage.color,
                            handleChange: () => {
                                const val = { ...data, ...form.values };
                                updateAssets({
                                    assets: {
                                        variables: {
                                            data: JSON.parse(JSON.stringify(val)),
                                            type: "string",
                                        },
                                    },
                                });
                            },
                        }}
                        inputBlock={{ label: t("Background Color") }}
                    />
                    {/* {<ErrorMessage name="backgroundImage" component="div" className="invalid-feedback" />} */}
                    {/* {form.errors.backgroundImage ? (
                        <div className={classes.errorMessage}>{form.errors.backgroundImage}</div>
                    ) : null} */}
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.mainColors.fg",
                            defaultValue: data.global.mainColors
                                ? ensureColorHelper(data.global.mainColors.fg)
                                : global.mainColors.fg,
                            handleChange: () => {
                                const val = { ...data, ...form.values };
                                updateAssets({
                                    assets: {
                                        variables: {
                                            data: JSON.parse(JSON.stringify(val)),
                                            type: "string",
                                        },
                                    },
                                });
                            },
                        }}
                        inputBlock={{ label: t("Text") }}
                    />

                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.links.colors.default",
                            defaultValue: data.global.links
                                ? ensureColorHelper(data.global.links.colors.default)
                                : global.links.colors.default,
                            handleChange: () => {
                                const val = { ...data, ...form.values };
                                updateAssets({
                                    assets: {
                                        variables: {
                                            data: JSON.parse(JSON.stringify(val)),
                                            type: "string",
                                        },
                                    },
                                });
                            },
                        }}
                        inputBlock={{ label: t("Links") }}
                    />
                </ThemeBuilderSection>

                <ThemeBuilderSection label={t("Buttons & Inputs")}>
                    <InputNumberBlock
                        inputNumber={{
                            variableID: "global.border.radius",
                            defaultValue: globalVariables().border.radius,
                        }}
                        inputBlock={{ label: t("Border Radius") }}
                    />

                    <ThemeBuilderSectionGroup label={t("Primary Buttons")}>
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primary",
                                defaultValue: data.buttonGlobals
                                    ? ensureColorHelper(data.buttonGlobals.colors.primary)
                                    : buttonGlobals.colors.primary,
                                handleChange: () => {
                                    const val = { ...data, ...form.values };
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(val)),
                                                type: "string",
                                            },
                                        },
                                    });
                                },
                            }}
                            inputBlock={{ label: t("Background") }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primaryContrast",
                                defaultValue: data.buttonGlobals
                                    ? ensureColorHelper(data.buttonGlobals.colors.primaryContrast)
                                    : buttonGlobals.colors.primaryContrast,
                                handleChange: () => {
                                    const val = { ...data, ...form.values };
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(val)),
                                                type: "string",
                                            },
                                        },
                                    });
                                },
                            }}
                            inputBlock={{ label: t("Text") }}
                        />
                    </ThemeBuilderSectionGroup>

                    <ThemeBuilderSectionGroup label={t("Secondary Buttons")}>
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.bg",
                                defaultValue: data.buttonGlobals
                                    ? ensureColorHelper(data.buttonGlobals.colors.bg)
                                    : buttonGlobals.colors.bg,
                                handleChange: () => {
                                    const val = { ...data, ...form.values };
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(val)),
                                                type: "string",
                                            },
                                        },
                                    });
                                },
                            }}
                            inputBlock={{ label: t("Background") }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.fg",
                                defaultValue: data.buttonGlobals
                                    ? ensureColorHelper(data.buttonGlobals.colors.fg)
                                    : buttonGlobals.colors.fg,
                                handleChange: () => {
                                    const val = { ...data, ...form.values };
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(val)),
                                                type: "string",
                                            },
                                        },
                                    });
                                },
                            }}
                            inputBlock={{ label: t("Text") }}
                        />
                    </ThemeBuilderSectionGroup>
                </ThemeBuilderSection>
            </Form>
        </FormikProvider>
    );
}
