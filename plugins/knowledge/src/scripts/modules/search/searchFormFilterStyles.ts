/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { cssOut } from "@dashboard/compatibilityStyles";
import { important } from "csx";

export const searchFormFilterVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("searchFormFilters");

    const spacer = makeThemeVars("spacer", {
        default: globalVars.gutter.size,
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
        $nest: {
            "&:before": {
                display: important("none"),
            },
        },
    });

    const submit = style("submit", {
        display: "block",
        ...margins({
            left: "auto",
            top: unit(24),
        }),
    });

    cssOut(".suggestedTextInput-option", suggestedTextStyleHelper().option);

    return {
        root,
        title,
        submit,
    };
});
