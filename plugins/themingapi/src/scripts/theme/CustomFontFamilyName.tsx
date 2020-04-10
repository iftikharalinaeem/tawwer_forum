/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import {
    useThemeBuilder,
    useThemeVariableField,
} from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import InputTextBlock from "@library/forms/InputTextBlock";

export const customFontFamilyNameKey = "global.fonts.customFontFamilyName";

export function CustomFontFamilyName() {
    const { setVariableValue } = useThemeBuilder();
    const { generatedValue, initialValue, rawValue } = useThemeVariableField(customFontFamilyNameKey);
    return (
        <InputTextBlock
            inputProps={{
                defaultValue: initialValue,
                value: generatedValue ?? rawValue,
                onChange: event => {
                    setVariableValue(customFontFamilyNameKey, event.target.value);
                },
            }}
        />
    );
}
