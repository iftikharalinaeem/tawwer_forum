/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import { debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { px } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { iconClasses } from "@library/icons/iconStyles";

const debug = debugHelper("knowledgeBaseIcons");

export function knowledgeBaseItem(className?: string) {
    const title = t("Knowledge Base Item");
    const globalVars = globalVariables();

    return (
        <svg
            viewBox="0 0 15.836 21.596"
            xmlns="http://www.w3.org/1999/xlink"
            className={classNames(
                className,
                style({
                    ...debug.name("knowledgeBaseIcon"),
                    width: px(16),
                    height: px(24.55),
                    transform: "translateY(3.5px)",
                    color: globalVars.mainColors.fg.toString(),
                }),
            )}
            aria-hidden="true"
        >
            <title>{title}</title>

            <path
                d="M.45,15.276c0-.448-.03-1.191,2.119-1.2V.45"
                transform="translate(0.005 0.002)"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "0.9px" }}
            />
            <path
                d="M2.569,14.075s6.655.008,9.362.008,3.449.106,3.449-2.392"
                transform="translate(0.005 0.002)"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "0.9px" }}
            />
            <polygon
                points="4.162 14.085 4.162 18.356 5.172 17.66 6.182 18.356 6.182 14.085 4.162 14.085"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "0.9px" }}
            />
            <path
                d="M12.95,14.36l.006,2"
                transform="translate(0.005 0.002)"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "0.9px" }}
            />
            <path
                d="M6.511,16.429h7.97c.705,0,.9-.478.9-1.067V1.518c0-.59-.194-1.068-.9-1.068H1.475a.967.967,0,0,0-1.028.9.9.9,0,0,0,0,.164V15.36a.968.968,0,0,0,.862,1.064.986.986,0,0,0,.163,0H3.822"
                transform="translate(0.005 0.002)"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "0.9px" }}
            />
            <path
                d="M5.45,4.86h7"
                transform="translate(0.005 0.002)"
                style={{ fill: "none", stroke: "currentColor", strokeLinecap: "square" }}
            />
            <path
                d="M5.45,6.86h7"
                transform="translate(0.005 0.002)"
                style={{ fill: "none", stroke: "currentColor", strokeLinecap: "square" }}
            />
        </svg>
    );
}

export function knowledgeBaseNoIcon(className?: string) {
    const title = t("Knowledge Base");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 50 69"
            className={classNames(iconClasses().standard, "icon-knowledgeBase", className)}
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                d="M7.32,2.1H9.507V51.341l-1.089,0c-3.679.014-5.06.737-5.372,1.9a4.209,4.209,0,0,0-.1,1.181v.066H.758v-.065a6.028,6.028,0,0,1,.176-1.751c.566-2.1,2.548-3.289,6.386-3.485Z"
                fill="currentColor"
            />
            <path
                d="M8.412,51.345l0-2.187h.9l2.449,0,8,.008,4.883,0c5.532.005,9.925.008,12.755.008h.957c4.753.008,6.12-.114,7.132-.748,1.046-.654,1.5-2.148,1.5-5.586h2.188c0,4.125-.651,6.267-2.527,7.44-1.534.96-2.992,1.09-8.3,1.082h-.953c-2.831,0-7.225,0-12.757-.008l-4.883-.005-8-.009-2.45,0Z"
                fill="currentColor"
            />
            <path
                d="M12.237,69.018V49.183h8.444V69.018l-4.222-3.6Zm2.187-4.742,2.035-1.736,2.034,1.736V51.37H14.424Z"
                fill="currentColor"
            />
            <polygon
                points="43.542 49.851 45.73 49.846 45.757 60.667 43.569 60.672 43.542 49.851"
                fill="currentColor"
            />
            <path
                d="M20.623,61.763V59.576H44.132A2.85,2.85,0,0,0,47,56.738V5.782a2.849,2.849,0,0,0-2.863-2.836H5.809A2.849,2.849,0,0,0,2.946,5.782V56.738a2.85,2.85,0,0,0,2.863,2.838h6.485v2.187H5.809A5.038,5.038,0,0,1,.758,56.738V5.782A5.038,5.038,0,0,1,5.809.758H44.132a5.038,5.038,0,0,1,5.051,5.024V56.738a5.039,5.039,0,0,1-5.051,5.025Z"
                fill="currentColor"
            />
            <path
                d="M17.984,22.222a.926.926,0,0,1,0-1.852H37.617a.926.926,0,0,1,0,1.852Zm0,5.556a.926.926,0,1,1,0-1.852H37.617a.926.926,0,1,1,0,1.852Z"
                fill="currentColor"
            />
        </svg>
    );
}
