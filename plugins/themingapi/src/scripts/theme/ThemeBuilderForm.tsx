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
import { formatUrl } from "@vanilla/library/src/scripts/utility/appUtils";
import { Form, FormikProvider, useFormik } from "formik";
import React, { useState } from "react";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeVariables } from "./themeEditorReducer";

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
    console.log("==>", props.variables);
    const form = useFormik({
        initialValues: {},
        onSubmit: async values => {
            await saveTheme();
            window.location.href = formatUrl("/theme/theme-settings", true);
        },
    });

    return (
        <FormikProvider value={form}>
            {/* The translate shouldn't be mandatory, it's a bug in this version of Formik */}
            <Form translate={true} className={classes.root}>
                <ThemeBuilderTitle />

                <ColorPickerBlock
                    colorPicker={{
                        variableID: "global.mainColors.primary",
                        defaultValue: ensureColorHelper(data.global.mainColors.primary),
                        handleChange: () => {
                            const data = JSON.stringify(form.values);
                            updateAssets({
                                assets: {
                                    variables: {
                                        data: JSON.parse(JSON.stringify(form.values)),
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
                            defaultValue: global.body.backgroundImage.color,
                            handleChange: () => {
                                updateAssets({
                                    assets: {
                                        variables: {
                                            data: JSON.parse(JSON.stringify(form.values)),
                                            type: "string",
                                        },
                                    },
                                });
                            },
                        }}
                        inputBlock={{ label: t("Background Color") }}
                    />

                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.mainColors.fg",
                            defaultValue: ensureColorHelper(data.global.mainColors.fg),
                            handleChange: () => {
                                updateAssets({
                                    assets: {
                                        variables: {
                                            data: JSON.parse(JSON.stringify(form.values)),
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
                            defaultValue: global.links.colors.default,
                            handleChange: () => {
                                updateAssets({
                                    assets: {
                                        variables: {
                                            data: JSON.parse(JSON.stringify(form.values)),
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
                    <ThemeBuilderSectionGroup label={t("Primary Buttons")}>
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primary",
                                defaultValue: buttonGlobals.colors.primary,
                                handleChange: () => {
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(form.values)),
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
                                defaultValue: buttonGlobals.colors.primaryContrast,
                                handleChange: () => {
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(form.values)),
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
                                defaultValue: buttonGlobals.colors.bg,
                                handleChange: () => {
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(form.values)),
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
                                defaultValue: buttonGlobals.colors.fg,
                                handleChange: () => {
                                    updateAssets({
                                        assets: {
                                            variables: {
                                                data: JSON.parse(JSON.stringify(form.values)),
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
