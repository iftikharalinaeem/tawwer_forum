/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const searchFormFilterVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("searchFormFilters");

    const spacer = makeThemeVars("spacer", {
        default: globalVars.spacer.size,
    });

    return {
        spacer,
    };
});

export const searchFormFilterClasses = useThemeCache(() => {
    const vars = searchFormFilterVariables();
    const style = styleFactory("searchFormFilters");

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
