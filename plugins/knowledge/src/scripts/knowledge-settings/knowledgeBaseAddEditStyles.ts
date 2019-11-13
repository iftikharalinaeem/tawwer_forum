/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";

export const knowledgeBaseAddEditClasses = useThemeCache(() => {
    const style = styleFactory("knowledgeBaseAddEdit");

    const heading = style("heading", {
        $nest: {
            "&&": {
                flexGrow: 0,
                flexShrink: 1,
            },
        },
    });

    return { heading };
});
