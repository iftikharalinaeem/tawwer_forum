/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { flexHelper, unit, spinnerLoader, colorOut } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { GlobalsNumber } from "csstype";
import { important, percent, px, ColorHelper } from "csx";
import { buttonVariables } from "@library/styles/buttonStyles";

export const reactionClasses = useThemeCache(() => {
    const style = styleFactory("reactions");
    const vars = globalVariables();
    const buttonVars = buttonVariables();

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
});
