/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeEditorClasses } from "./themeEditorStyles";

interface IProps {}

export default function ThemeEditor(props: IProps) {
    const classes = themeEditorClasses();
    return (
        <>
            <div className={classes.wrapper}>
                <div className={classes.frame}></div>
                <div className={classes.styleOptions}></div>
            </div>
        </>
    );
}
