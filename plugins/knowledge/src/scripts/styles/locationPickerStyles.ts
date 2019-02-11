/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { px } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, srOnly } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { layoutVariables } from "@library/styles/layoutStyles";

export default function locationPickerClasses(theme?: object) {
    const globalVars = globalVariables(theme);

    const debug = debugHelper("locationPicker");

    const root = style({
        ...debug.name(),
    });

    return {
        root,
    };
}
