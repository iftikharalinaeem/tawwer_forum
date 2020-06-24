/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { iconClasses } from "@vanilla/library/src/scripts/icons/iconStyles";
import classNames from "classnames";

interface IProps {
    className?: string;
}

export function ProductIcon(props: IProps) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, props.className)}
        >
            <g fill="none" fillRule="evenodd">
                <path
                    stroke="#555A62"
                    d="M11.818 2.972L3.04 7.21l8.784 4.833a.187.187 0 00.152.002l8.787-5.154-8.945-3.919z"
                ></path>
                <path fill="#555A62" fillRule="nonzero" d="M12.5 12v9.5h-1V12z"></path>
                <path
                    fill="#555A62"
                    fillRule="nonzero"
                    d="M2.8 7v8.719l9.339 5.691L21 15.727V7h1v9.273l-9.847 6.317L1.8 16.281V7z"
                ></path>
            </g>
        </svg>
    );
}
