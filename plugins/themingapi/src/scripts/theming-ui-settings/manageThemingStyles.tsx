/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory } from "@vanilla/library/src/scripts/styles/styleUtils";
import { media } from "typestyle";

export function manageThemingClasses() {
    const style = styleFactory("manageTheming");

    const root = style({
        overflowX: "hidden",
    });

    const grid = style(
        "grid",
        {
            display: ["flex", "grid"],
            flexWrap: "wrap",
            justifyContent: "flex-start",
            padding: 24,
            marginLeft: -36,
            marginRight: -36,
            gridTemplateColumns: "repeat(3, 1fr)",
            gridAutoRows: "minmax(240px, auto)",
        },
        media(
            { maxWidth: 1300 },
            {
                gridTemplateColumns: "repeat(2, 1fr)",
            },
        ),
        media(
            { maxWidth: 600 },
            {
                gridTemplateColumns: "repeat(1, 1fr)",
            },
        ),
    );

    const gridItem = style("gridItem", {
        flex: 1,
        padding: 18,
        display: "flex",
        flexDirection: "column",
    });

    return { root, grid, gridItem };
}
