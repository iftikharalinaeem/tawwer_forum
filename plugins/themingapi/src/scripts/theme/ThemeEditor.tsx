/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ThemeBuilderForm from "./ThemeBuilderForm";
import { IThemeVariables } from "./themeEditorReducer";
import { themeEditorClasses } from "@library/forms/themeEditor/themeEditorStyles";

export interface IProps {
    themeID: string | number;
    variables?: IThemeVariables;
}

export default function ThemeEditor(props: IProps) {
    const classes = themeEditorClasses();
    return (
        <div className={classes.wrapper}>
            <div className={classes.frameContainer}>
                {/* <iframe
                    src="https://dev.vanilla.localhost/theme/theme-settings/preview"
                    width="100%"
                    height="100%"
                    className={classes.frame}
                ></iframe> */}
            </div>
            <div className={classes.styleOptions}>
                <ThemeBuilderForm variables={props.variables} />
            </div>
        </div>
    );
}
