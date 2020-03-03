/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { color, percent } from "csx";
import { storiesOf } from "@storybook/react";
import { Form, FormikProvider, useFormik } from "formik";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";
import ThemeBuilderTitle from "@library/forms/themeEditor/ThemeBuilderTitle";
import ThemeBuilderSection from "@library/forms/themeEditor/ThemeBuilderSection";
import ThemeBuilderSectionGroup from "@library/forms/themeEditor/ThemeBuilderSectionGroup";
import InputNumberBlock from "@library/forms/themeEditor/InputNumberBlock";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import { InputDropDownBlock } from "@vanilla/library/src/scripts/forms/themeEditor/InputDropDownBlock";
import { ThemePresetDropDown } from "@themingapi/theme/ThemePresetDropDown";

const story = storiesOf("Theme", module);

story.add("Theme Builder Form - Test Cases", () => {
    const form = useFormik({
        initialValues: {},
        onSubmit: values => {
            // console.log(values);
        },
    });

    return (
        <StoryContent>
            <StoryHeading depth={1}>Theme Builder</StoryHeading>
            <aside style={{}} className={themeBuilderClasses().root}>
                <FormikProvider value={form}>
                    {/* The translate shouldn't be mandatory, it's a bug in this version of Formik */}
                    <Form translate="yes">
                        <ThemeBuilderTitle />
                        <ThemePresetDropDown />
                        <InputDropDownBlock
                            inputBlock={{
                                label: "Drop Down - no default",
                            }}
                            inputDropDown={{
                                variableID: "example.single.value.dropdown",
                                options: [
                                    {
                                        value: "toto",
                                        label: "toto",
                                    },
                                    {
                                        value: "tata",
                                        label: "tata",
                                    },
                                    {
                                        value: "titi",
                                        label: "titi",
                                    },
                                ],
                            }}
                        />
                        <InputDropDownBlock
                            inputBlock={{
                                label: "Drop Down - with default",
                            }}
                            inputDropDown={{
                                variableID: "example.single.value.dropdown.selected",
                                defaultValue: "titi",
                                options: [
                                    {
                                        value: "toto",
                                        label: "toto",
                                    },
                                    {
                                        value: "tata",
                                        label: "tata",
                                    },
                                    {
                                        value: "titi",
                                        label: "titi",
                                    },
                                ],
                            }}
                        />
                        <InputNumberBlock
                            inputNumber={{
                                variableID: "global.something.or.other.number.test1",
                                defaultValue: 10,
                            }}
                            inputBlock={{
                                label: "Test 9 - Number!",
                            }}
                        />
                        <InputNumberBlock
                            inputNumber={{
                                variableID: "global.something.or.other.number.test2",
                            }}
                            inputBlock={{
                                label: "Test 9 - Number!",
                            }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "global.something.or.other.color.1",
                                defaultValue: color("#ca0000"),
                            }}
                            inputBlock={{ label: "Test 1" }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "global.something.or.other.color.2",
                                defaultValue: color("#00ca25"),
                            }}
                            inputBlock={{ label: "Test 2" }}
                        />
                        <ThemeBuilderSection label={"Section 1"}>
                            <ColorPickerBlock
                                colorPicker={{
                                    variableID: "global.something.or.other.color.3",
                                    defaultValue: color("#3139ca"),
                                }}
                                inputBlock={{ label: "Test 3" }}
                            />
                            <ColorPickerBlock
                                colorPicker={{
                                    variableID: "global.something.or.other.color.4",
                                    defaultValue: color("#c627ca"),
                                }}
                                inputBlock={{ label: "Test 4" }}
                            />
                        </ThemeBuilderSection>
                        <ThemeBuilderSection label={"Section 31"}>
                            <ColorPickerBlock
                                colorPicker={{
                                    variableID: "global.something.or.other.color.5",
                                    defaultValue: color("#c7cac4"),
                                }}
                                inputBlock={{ label: "Test 5" }}
                            />
                            <ColorPickerBlock
                                colorPicker={{
                                    variableID: "global.something.or.other.color.6",
                                    defaultValue: color("#15206f"),
                                }}
                                inputBlock={{ label: "Test 6" }}
                            />
                            <ThemeBuilderSectionGroup label={"Section Sub Group"}>
                                <ColorPickerBlock
                                    colorPicker={{
                                        variableID: "global.something.or.other.color.7",
                                        defaultValue: "cat" as any, // Intentionally bypassing typescript for error
                                    }}
                                    inputBlock={{ label: "With Error" }}
                                />
                                <ColorPickerBlock
                                    colorPicker={{
                                        variableID: "global.something.or.other.color.8",
                                        defaultValue: "chinchilla" as any, // Intentionally bypassing typescript for error
                                    }}
                                    inputBlock={{ label: "With Error" }}
                                />
                                <ColorPickerBlock
                                    colorPicker={{
                                        variableID: "global.something.or.other.color.9",
                                    }}
                                    inputBlock={{ label: "Test 8 - No default value" }}
                                />
                            </ThemeBuilderSectionGroup>
                        </ThemeBuilderSection>
                    </Form>
                </FormikProvider>
            </aside>
        </StoryContent>
    );
});
