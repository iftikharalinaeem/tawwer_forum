/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";

export const searchInClasses = useThemeCache(() => {
    const style = styleFactory("searchIn");
    const vars = globalVariables();

    const separator = style("separator", {});

    return {
        separator,
    };
});
