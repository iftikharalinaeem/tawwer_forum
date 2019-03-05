/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/styleUtils";
import { percent, px } from "csx";
import { style } from "typestyle";

export const locationPickerClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const debug = debugHelper("locationPicker");

    const root = style({
        ...debug.name(),
    });

    const articlePlaceholder = style({
        ...debug.name("articlePlaceholder"),
        display: "block",
        width: percent(100),
        height: px(24),
        border: `dotted 1px ${globalVars.mixBgAndFg(0.5).toString()}`,
        margin: `${px(6)} ${px(12)}`,
        borderRadius: px(2),
        $nest: {
            "&:hover": {
                backgroundColor: globalVars.mainColors.primary.fade(0.1).toString(),
            },
            "&:focus": {
                backgroundColor: globalVars.mainColors.primary.fade(0.1).toString(),
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

    const instructions = style({
        ...debug.name("instructions"),
        fontSize: px(globalVars.fonts.size.medium),
        padding: `${px(8)} ${px(12)}`,
        borderBottom: `solid 1px ${globalVars.mixBgAndFg(0.15)}`,
        width: percent(100),
    });

    return {
        root,
        articlePlaceholder,
        instructions,
    };
});
