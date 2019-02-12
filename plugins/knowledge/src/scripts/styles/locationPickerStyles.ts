/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px } from "csx";
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

    const articlePlaceholder = style({
        display: "block",
        width: percent(100),
        height: px(24),
        border: `dotted 1px ${globalVars.mixBgAndFg(0.5).toString()}`,
        margin: `${px(6)} ${px(12)}`,
        borderRadius: px(2),
        $nest: {
            "&:hover": {
                backgroundColor: globalVars.mainColors.primary.fade(0.5).toString(),
            },
            "&:focus": {
                backgroundColor: globalVars.mainColors.primary.fade(0.5).toString(),
            },
            "&.focus-visible": {
                backgroundColor: globalVars.mainColors.primary.fade(0.8).toString(),
                borderColor: globalVars.mainColors.fg.toString(),
                borderStyle: "solid",
            },
            "&.isActive": {
                backgroundColor: globalVars.mainColors.primary.toString(),
            },
            "&.isFirst": {
                marginTop: px(18),
            },
            "&.isLast": {
                marginBottom: px(18),
            },
        },
    });

    return {
        root,
        articlePlaceholder,
    };
}
