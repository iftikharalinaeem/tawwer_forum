/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import { Form, FormikProvider, useFormik } from "formik";
import ThemeBuilderTitle from "@library/forms/themeEditor/ThemeBuilderTitle";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";
import ThemeBuilderSection from "@library/forms/themeEditor/ThemeBuilderSection";
import ThemeBuilderSectionGroup from "@library/forms/themeEditor/ThemeBuilderSectionGroup";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n/src";
import { buttonGlobalVariables, ButtonTypes } from "@library/forms/buttonStyles";
import InputNumberBlock from "@library/forms/themeEditor/InputNumberBlock";
import { ThemePresetDropDown } from "@library/forms/themeEditor/ThemePresetDropDown";
import { InputDropDownBlock } from "@library/forms/themeEditor/InputDropDownBlock";
import { color } from "csx";

export interface IThemeBuilderForm {}

export default function ThemeBuilderForm(props: IThemeBuilderForm) {
    const classes = themeBuilderClasses();
    const global = globalVariables();
    const buttonGlobals = buttonGlobalVariables();
    const form = useFormik({
        initialValues: {},
        onSubmit: values => {},
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
                        defaultValue: global.mainColors.primary,
                    }}
                    inputBlock={{ label: t("Brand Color") }}
                />

                <ThemeBuilderSection label={"Body"}>
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.body.backgroundImage.color",
                            defaultValue: global.body.backgroundImage.color,
                        }}
                        inputBlock={{ label: t("Background Color") }}
                    />

                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.mainColors.fg",
                            defaultValue: global.mainColors.fg,
                        }}
                        inputBlock={{ label: t("Text") }}
                    />

                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.links.colors.default",
                            defaultValue: global.links.colors.default,
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
                            defaultValue: globalVariables().border.radius,
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
                                defaultValue: buttonGlobals.colors.primary,
                            }}
                            inputBlock={{ label: t("Background") }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.primaryContrast",
                                defaultValue: buttonGlobals.colors.primaryContrast,
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
                                defaultValue: buttonGlobals.colors.bg,
                            }}
                            inputBlock={{ label: t("Background") }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "buttonGlobals.colors.fg",
                                defaultValue: buttonGlobals.colors.fg,
                            }}
                            inputBlock={{ label: t("Text") }}
                        />
                    </ThemeBuilderSectionGroup>
                </ThemeBuilderSection>
            </Form>
        </FormikProvider>
    );
}
