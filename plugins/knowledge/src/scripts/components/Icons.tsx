/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import classNames from "classnames";

const currentColorFill = {
    fill: "currentColor",
};

export function leftChevron(classes?: string) {
    const title = `<`;
    return (
        <svg
            className={classNames("icon", "icon-chevronLeft", classes)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{title}</title>
            <path
                d="M14.9,7.7l-4.4,4.4,4.4,4.4a.5.5,0,0,1,0,.6l-.6.6a.5.5,0,0,1-.6,0L8.5,12.5a.5.5,0,0,1,0-.6l5.2-5.2a.5.5,0,0,1,.6,0s.676.543.7.7A.325.325,0,0,1,14.9,7.7Z"
                style={currentColorFill}
            />
        </svg>
    );
}
