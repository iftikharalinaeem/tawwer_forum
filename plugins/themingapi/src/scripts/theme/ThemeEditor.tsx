/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { siteUrl } from "@library/utility/appUtils";
import { useIFrameCommunication } from "@themingapi/theme/IframeCommunicationContext";
import { themeEditorClasses } from "@themingapi/theme/ThemeEditor.styles";
import React from "react";
import ThemeBuilderForm from "./ThemeBuilderPanel";
import { IThemeVariables } from "@vanilla/library/src/scripts/theming/themeReducer";

export interface IProps {
    themeID: string | number;
    variables?: IThemeVariables;
    getSendMessage: (sendMessage: (message: {}) => void) => void;
}

export default function ThemeEditor(props: IProps) {
    const classes = themeEditorClasses();
    const { iframeRef, setIFrameRef, sendMessage } = useIFrameCommunication();

    return (
        <>
            <div className={classes.wrapper}>
                <div className={classes.frame}>
                    <iframe
                        ref={setIFrameRef}
                        src={siteUrl(`/theme/theme-settings/${props.themeID}/preview?revisionID=${props.revisionID}`)}
                        width="100%"
                        height="100%"
                        scrolling="yes"
                    ></iframe>
                    <div className={classes.shadowTop}></div>
                    <div className={classes.shadowRight}></div>
                </div>
                <div className={classes.panel}>
                    <ThemeBuilderForm />
                </div>
            </div>
        </>
    );
}
