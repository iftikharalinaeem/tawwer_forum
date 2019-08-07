/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { negative, unit } from "@library/styles/styleHelpers";

export const draftPreviewClasses = useThemeCache(() => {
    const style = styleFactory("draftPreview");

    const header = style("header", {
        display: "flex",
        alignItems: "flex-start",
    });

    const metas = style("metas", {
        marginTop: unit(negative(6)),
    });

    return {
        header,
        metas,
    };
});
