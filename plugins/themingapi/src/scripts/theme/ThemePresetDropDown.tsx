/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import classNames from "classnames";
import SelectOne from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { useField } from "formik";
import InputHidden from "@library/forms/themeEditor/InputHidden";
import { t } from "@vanilla/i18n/src";
import { color } from "csx";
import { useUniqueID } from "@library/utility/idUtils";
import ThemeBuilderBlock from "@library/forms/themeEditor/ThemeBuilderBlock";
import { inputDropDownClasses } from "@library/forms/themeEditor/inputDropDownStyles";
import { ensureColorHelper } from "@vanilla/library/src/scripts/forms/themeEditor/ColorPicker";

interface IThemePresetDropDown {
    presetFg: string;
    presetBg: string;
}
export const ThemePresetDropDown = (props: IThemePresetDropDown) => {
    const options: IComboBoxOption[] = [
        {
            label: t("Light"),
            value: "light",
            data: {
                fg: color("#555a62"),
                bg: color("#fff"),
            },
        },
        {
            label: t("Dark"),
            value: "dark",
            data: {
                fg: color("#fff"),
                bg: color("#555a62"),
            },
        },
    ];

    let defaultValue = {};

    if (
        options[0].data.fg === ensureColorHelper(props.presetFg) &&
        options[0].data.bg === ensureColorHelper(props.presetBg)
    ) {
        defaultValue = options[0];
    } else if (
        options[1].data.fg === ensureColorHelper(props.presetFg) &&
        options[1].data.bg === ensureColorHelper(props.presetBg)
    ) {
        defaultValue = options[1];
    } else {
        options.push({
            label: t("Custom"),
            value: "custom",
            data: {
                fg: ensureColorHelper(props.presetFg),
                bg: ensureColorHelper(props.presetBg),
            },
        });
        defaultValue = options[2];
    }

    const [currentOption, setCurrentOption] = useState(defaultValue);

    const fgID = "global.mainColors.fg";
    const bgID = "global.mainColors.bg";

    const [fgValue, fgValueMeta, fgValueHelpers] = useField(fgID);
    const [bgValue, bgValueMeta, bgValueHelpers] = useField(bgID);

    const onChange = (option: IComboBoxOption | undefined) => {
        if (option) {
            fgValueHelpers.setTouched(true);
            bgValueHelpers.setTouched(true);
            fgValueHelpers.setValue(option.data.fg.toHexString());
            bgValueHelpers.setValue(option.data.bg.toHexString());

            setCurrentOption(option as any);
        }
    };

    const inputID = useUniqueID("themePreset");
    const labelID = useUniqueID("themePresetLabel");

    return (
        <ThemeBuilderBlock label={t("Preset")} labelID={labelID} inputWrapClass={inputDropDownClasses().root}>
            <div className={classNames("input-wrap-right")}>
                <SelectOne
                    label={null}
                    labelID={labelID}
                    inputID={inputID}
                    options={options}
                    value={currentOption as any}
                    onChange={onChange}
                    isClearable={false}
                    disabled={currentOption["value"] === "custom" ? true : false}
                />
                <InputHidden variableID={fgID} value={fgValue.value} />
                <InputHidden variableID={bgID} value={bgValue.value} />
            </div>
        </ThemeBuilderBlock>
    );
};
