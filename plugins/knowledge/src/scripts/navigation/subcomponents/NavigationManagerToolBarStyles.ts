/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { borders, colorOut, margins, paddings, unit } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc } from "csx";

export const navigationManagerToolbarVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("navigationManagerToolbar");

    const colors = makeThemeVars("colors", {
        separator: globalVars.mixBgAndFg(0.8),
    });

    const spacer = makeThemeVars("spacer", {
        default: globalVars.spacer.size,
        bottomOffset: 6,
    });

    return {
        colors,
        spacer,
    };
});

export const navigationManagerToolbarClasses = useThemeCache(() => {
    const vars = navigationManagerToolbarVariables();
    const globalVars = globalVariables();
    const style = styleFactory("navigationManagerToolbar");
    let bottomMargin;
    if (isNaN(vars.spacer.default) || isNaN(vars.spacer.bottomOffset)) {
        bottomMargin = calc(`${unit(vars.spacer.default)} - ${unit(vars.spacer.bottomOffset)}`);
    } else {
        bottomMargin = unit(vars.spacer.default - vars.spacer.bottomOffset);
    }

    const root = style(
        margins({
            top: vars.spacer.default,
            horizontal: 0,
            bottom: bottomMargin,
        }),
    );

    // const root = style({});

    // const root = style({});

    // const root = style({});

    // const root = style({});

    // const root = style({});

    return {
        root,
    };
});
