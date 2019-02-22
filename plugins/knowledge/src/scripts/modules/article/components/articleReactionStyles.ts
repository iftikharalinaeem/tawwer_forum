/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { flexHelper, styleFactory } from "@library/styles/styleHelpers";
import { important } from "csx";

export function reactionClasses(theme?: object) {
    const style = styleFactory("reactions");
    const vars = globalVariables(theme);

    const frame = style({
        paddingTop: vars.baseUnit * 2,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });

    const title = style("title", {
        fontSize: vars.fonts.size.large,
    });

    const votingButton = style("votingButton", {
        textAlign: "center",
        margin: 8,
    });

    const checkedButtonContent = style(
        "checkButtonContent",
        {
            width: "100%",
        },
        flexHelper().middle(),
    );

    const checkedButton = style("checkedButton", {
        opacity: important(1) as any,
    });

    const votingButtons = style("votingButton", {
        padding: 8,
    });

    const resultText = style("resultText", {
        fontSize: vars.meta.fontSize,
        color: vars.meta.color.toString(),
    });

    const signInText = style("signIn", {
        fontSize: vars.fonts.size.large,
    });

    const link = style("link", {
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
