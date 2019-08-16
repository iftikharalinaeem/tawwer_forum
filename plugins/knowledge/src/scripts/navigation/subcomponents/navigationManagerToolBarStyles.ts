/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { margins, unit } from "@library/styles/styleHelpers";
import { calc, percent } from "csx";
import { singleBorder } from "@library/styles/styleHelpers";

export const navigationManagerToolbarVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("navigationManagerToolbar");

    const colors = makeThemeVars("colors", {
        separator: globalVars.mixBgAndFg(0.2),
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

    const icon = style("icon", {
        display: "inline-flex",
        verticalAlign: "middle",
    });

    const bar = style("bar", {
        display: "flex",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        position: "relative",
        width: percent(100),
        marginBottom: unit(3),
    });

    const newFolder = style("newFolder", {
        marginLeft: "auto",
        transform: `translateY(1.5px)`,
    });

    const separator = style("separator", {
        margin: "auto",
        width: calc(`100% - 24px`),
        borderBottom: singleBorder({
            color: vars.colors.separator,
        }),
    });

    const buttonLabel = style("buttonLabel", {
        marginLeft: unit(4),
    });

    return {
        root,
        icon,
        bar,
        newFolder,
        separator,
        buttonLabel,
    };
});
