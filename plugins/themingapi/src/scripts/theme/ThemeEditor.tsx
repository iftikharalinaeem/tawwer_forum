/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeEditorClasses } from "./themeEditorStyles";

export interface IProps {}

export default function ThemeEditor(props: IProps) {
    const classes = themeEditorClasses();
    return (
        <>
            <div className={classes.wrapper}>
                <div className={classes.frame}>
                    <iframe
                        src="https://dev.vanilla.localhost/theme/theme-settings/preview"
                        width="100%"
                        height="100%"
                    ></iframe>
                </div>
                <div className={classes.styleOptions}></div>
            </div>
        </>
    );
}
