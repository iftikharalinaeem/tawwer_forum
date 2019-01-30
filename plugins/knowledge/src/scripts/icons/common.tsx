/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import classNames from "classnames";

export function knowledgeBaseNoIcon(className?: string) {
    const title = t("Knowledge Base");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 50 69"
            className={classNames("icon", "icon-knowledgeBase", className)}
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
