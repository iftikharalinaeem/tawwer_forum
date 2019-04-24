/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";

export const draftPreviewClasses = useThemeCache(() => {
    const style = styleFactory("draftPreview");

    const header = style("header", {
        display: "flex",
        alignItems: "flex-start",
    });

    return {
        header,
    };
});
