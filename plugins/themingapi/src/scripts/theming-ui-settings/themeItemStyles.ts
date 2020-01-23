/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";

export const themeItemClasses = useThemeCache(() => {
    const style = styleFactory("themeItem");
    const vars = globalVariables();

    const item = style("item", {});

    const title = style("title", {
        fontSize: vars.fonts.size.medium,
        fontWeight: vars.fonts.weights.semiBold,
    });
    return { item, title };
});
