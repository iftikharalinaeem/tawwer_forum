/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { flexHelper } from "@library/styles/styleHelpers";
import { important, percent, px } from "csx";
import { GlobalsNumber } from "csstype";
import { unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";

export function reactionClasses(theme?: object) {
    const style = styleFactory("reactions");
    const vars = globalVariables(theme);

    const frame = style({
        paddingTop: px(12),
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });

    const title = style("title", {
        fontSize: vars.fonts.size.large,
    });

    const votingButton = style("votingButton", {
        textAlign: "center",
        margin: px(8),
    });

    const checkedButtonContent = style(
        "checkButtonContent",
        {
            width: percent(100),
        },
        flexHelper().middle(),
    );

    const checkedButton = style("checkedButton", {
        opacity: important(1) as GlobalsNumber,
    });

    const votingButtons = style("votingButton", {
        padding: px(8),
    });

    const resultText = style("resultText", {
        fontSize: unit(vars.meta.text.fontSize),
        color: vars.meta.text.color.toString(),
    });

    const signInText = style("signIn", {
        fontSize: vars.fonts.size.large,
    });

    const link = style("link", {
        color: vars.links.colors.toString(),
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
