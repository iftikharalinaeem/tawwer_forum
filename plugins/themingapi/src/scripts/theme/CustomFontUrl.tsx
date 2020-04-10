/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import {
    useThemeBuilder,
    useThemeVariableField,
} from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import InputTextBlock from "@library/forms/InputTextBlock";

export const customFontUrlKey = "global.fonts.customFontUrl";

interface IProps {
    onChange: (val: string) => void;
}

export function CustomFontUrl(props: IProps) {
    const { setVariableValue } = useThemeBuilder();
    const { generatedValue, initialValue, rawValue } = useThemeVariableField(customFontUrlKey);
    return (
        <InputTextBlock
            inputProps={{
                defaultValue: initialValue,
                value: generatedValue ?? rawValue,
                onChange: event => {
                    setVariableValue(customFontUrlKey, event.target.value);
                    props.onChange(event.target.value);
                },
            }}
        />
    );
}
