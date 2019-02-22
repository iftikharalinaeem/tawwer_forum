/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { style } from "typestyle";
import { flexHelper } from "@library/styles/styleHelpers";
import { important } from "csx";

export function reactionClasses(theme?: object) {
    const vars = globalVariables(theme);

    const frame = style({
        paddingTop: vars.baseUnit * 2,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });

    const title = style({
        fontSize: vars.fonts.size.large,
    });

    const votingButton = style({
        textAlign: "center",
        margin: 8,
    });

    const checkedButtonContent = style(
        {
            width: "100%",
        },
        flexHelper().middle(),
    );

    const checkedButton = style({
        opacity: important(1) as any,
    });

    const votingButtons = style({
        padding: 8,
    });

    const resultText = style({
        fontSize: vars.meta.fontSize,
        color: vars.meta.color.toString(),
    });

    const signInText = style({
        fontSize: vars.fonts.size.large,
    });

    const link = style({
        color: vars.links.color.toString(),
        fontWeight: vars.fonts.weights.bold,
    });

    return {
        link,
        title,
        frame,
        votingButton,
        checkedButtonContent,
        checkedButton,
        votingButtons,
        resultText,
        signInText,
    };
}
