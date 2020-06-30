/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { negative, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { searchResultsVariables } from "@library/features/search/searchResultsStyles";
import { percent, translate, translateY } from "csx";

export const draftPreviewClasses = useThemeCache(() => {
    const vars = globalVariables();
    const searchResultVars = searchResultsVariables();
    const style = styleFactory("draftPreview");

    const header = style("header", {
        display: "flex",
        alignItems: "flex-start",
        marginBottom: unit(6),
    });

    const metas = style("metas", {
        marginTop: unit(0),
    });

    const actions = style("actions", {
        height: unit(searchResultVars.title.font.size * searchResultVars.title.font.lineHeight),
        position: "relative",
    });

    const toggle = style("toggle", {
        position: "relative",
        top: percent(50),
        transform: translate(`${unit(vars.buttonIcon.offset)}`, `-50%`),
    });

    const option = style("option", {});

    return {
        header,
        metas,
        toggle,
        actions,
        option,
    };
});
