/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { flexHelper } from "@vanilla/library/src/scripts/styles/styleHelpers";

export const knowledgeBaseAddEditClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("knowledgeBaseAddEdit");

    const heading = style("heading", {
        $nest: {
            "&&": {
                flexGrow: 0,
                flexShrink: 1,
            },
        },
    });

    const searchContainer = style("searchContainer", {
        ...flexHelper().middleLeft(),
        justifyContent: "space-between",
    });

    const searchMeta = style("searchMeta", {});

    const searchBar = style("searchBar", {
        maxWidth: 600,
        marginBottom: globalVars.gutter.size,
        flex: 1,
    });

    return { heading, searchBar, searchContainer, searchMeta };
});
