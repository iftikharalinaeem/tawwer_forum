/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { colorOut, paddings, unit, borders, userSelect, EMPTY_BORDER } from "@library/styles/styleHelpers";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const pageLocationVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("pageLocation");

    const spacer = makeThemeVars("spacing", {
        default: 15,
    });

    const picker = makeThemeVars("colors", {
        color: globalVars.mixBgAndFg(0.83),
        padding: {
            vertical: 8,
            left: spacer.default,
            right: spacer.default * 1.5,
        },
    });

    const icon = makeThemeVars("icon", {
        opacity: 0.8,
    });

    return { spacer, picker, icon };
});

export const pageLocationClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formVars = formElementsVariables();
    const vars = pageLocationVariables();
    const style = styleFactory("folderContents");

    const root = style({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "flex-start",
        marginBottom: unit(globalVars.spacer.size),
        minHeight: formVars.sizing.height,
        cursor: "pointer",
    });

    const picker = style("picker", {
        ...shadowHelper().embed(),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        fontWeight: globalVars.fonts.weights.semiBold,
        ...borders({
            ...EMPTY_BORDER,
            radius: formVars.sizing.height / 2,
        }),
        ...userSelect(),
        marginRight: unit(8),
        fontSize: unit(globalVars.fonts.size.small),
        color: colorOut(vars.picker.color),
        ...paddings(vars.picker.padding),
        $nest: {
            "&:active": {
                borderColor: colorOut(globalVars.mainColors.primary),
                color: colorOut(globalVars.mainColors.primary),
            },
            "&:hover": {
                borderColor: colorOut(globalVars.mainColors.primary),
                color: colorOut(globalVars.mainColors.primary),
            },
            "&:focus": {
                borderColor: colorOut(globalVars.mainColors.primary),
                color: colorOut(globalVars.mainColors.primary),
            },
            "&.focus-visible": {
                borderColor: colorOut(globalVars.mainColors.primary),
                color: colorOut(globalVars.mainColors.primary),
            },
            "&&:not(.focus-visible)": {
                outline: 0,
            },
        },
    });

    return {
        root,
        picker,
    };
});
