import { colorOut, debugHelper, flexHelper, ISpinnerProps, margins, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { percent, px, viewHeight } from "csx";
import { style } from "typestyle";

export const pageErrorMessageClasses = () => {
    const debug = debugHelper("");
    const style = styleFactory("pageErrorMessage");
    const globalVars = globalVariables();

    const root = style({
        marginBottom: viewHeight(25),
    });

    const title = style("title", {
        fontSize: unit(globalVars.fonts.size.smallTitle),
        lineHeight: globalVars.lineHeights.condensed,
        fontWeight: globalVars.fonts.weights.semiBold,
        textAlign: "center",
    });

    const description = style("description", {
        textAlign: "center",
        fontSize: unit(globalVars.fonts.size.large),
        marginTop: unit(12),
    });

    const cta = style("cta", {
        display: "block",
        ...margins({
            top: unit(21),
            horizontal: "auto",
        }),
    });

    const titleAsParagraph = style("titleAsParagraph", {
        fontSize: globalVars.fonts.size.large,
    });

    const errorIcon = style("icon", {
        display: "block",
        margin: "auto",
        color: colorOut(globalVars.mainColors.primary),
        height: unit(85),
        width: unit(85),
        marginBottom: unit(12),
    });

    return {
        root,
        title,
        description,
        cta,
        titleAsParagraph,
        errorIcon,
    };
};
