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

export const advancedSearchVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("advancedSearch");

    const spacer = makeThemeVars("spacer", {
        default: globalVars.spacer.size,
    });

    return {
        spacer,
    };
});

export const advancedSearchClasses = useThemeCache(() => {
    const vars = advancedSearchVariables();
    const globalVars = globalVariables();
    const style = styleFactory("advancedSearch");

    const root = style({
        display: "block",
        position: "relative",
    });

    const title = style("title", {
        marginBottom: unit(vars.spacer.default),
    });

    const submit = style("submit", {
        display: "block",
        ...margins({
            left: "auto",
            top: unit(24),
        }),
    });

    return {
        root,
        title,
        submit,
    };
});
