/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { percent } from "csx";

export const themeItemClasses = useThemeCache(() => {
    const style = styleFactory("themeItem");
    const vars = globalVariables();

    const item = style("item", {
        padding: 18,
        width: "calc(100% / 3)",
    });

    const title = style("title", {
        fontSize: vars.fonts.size.medium,
        fontWeight: vars.fonts.weights.semiBold,
    });
    return { item, title };
});
