/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { useThemeBuilder } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import InputTextBlock from "@library/forms/InputTextBlock";

export function FontFamilyName() {
    const { setVariableValue } = useThemeBuilder();

    return <InputTextBlock />;
}
