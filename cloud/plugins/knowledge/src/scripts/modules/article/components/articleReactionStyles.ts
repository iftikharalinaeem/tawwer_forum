/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, flexHelper, margins, unit } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { GlobalsNumber } from "csstype";
import { important, percent, px } from "csx";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { cssOut } from "@dashboard/compatibilityStyles";

export const reactionClasses = useThemeCache(() => {
    const style = styleFactory("articleReactions");
    const vars = globalVariables();

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
        fontSize: unit(vars.meta.text.size),
        color: colorOut(vars.meta.text.color),
        marginBottom: unit(12),
    });

    const reCaptchaText = style("reCaptchaText", {
        fontSize: unit(vars.meta.text.size),
        color: colorOut(vars.meta.text.color),
        maxWidth: percent(100),
        width: unit(300),
        ...margins({
            horizontal: "auto",
        }),
    });

    const reCaptchaContainer = style("reCaptchaContainer", {
        textAlign: "center",
        maxWidth: percent(100),
        width: unit(300),
    });

    const signInText = style("signInText", {
        fontSize: vars.fonts.size.large,
        marginBottom: px(8),
    });

    const linkColors = clickableItemStates();
    const link = style("link", {
        fontWeight: vars.fonts.weights.bold,
        color: colorOut(linkColors.color as string),
        $nest: linkColors.$nest,
    });

    cssOut(`.grecaptcha-badge`, {
        visibility: "hidden",
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
        reCaptchaText,
        reCaptchaContainer,
    };
});
