/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeInputText } from "@library/forms/themeEditor/ThemeInputText";

export function CustomFontFamily() {
    return <ThemeInputText varKey={"global.fonts.customFont.name"} />;

    // return (
    //     <InputTextBlock
    //         inputProps={{
    //             defaultValue: initialValue,
    //             value: generatedValue,
    //             onChange: event => {
    //                 setVariableValue(customFontFamilyKey, event.target.value);
    //             },
    //         }}
    //     />
    // );
}
