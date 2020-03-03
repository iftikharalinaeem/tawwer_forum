/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef } from "react";
import ThemeBuilderForm from "./ThemeBuilderForm";
import { IThemeVariables } from "./themeEditorReducer";
import { themeEditorClasses } from "@library/forms/themeEditor/themeEditorStyles";
import { useIFrameCommunication } from "@themingapi/theme/IframeCommunicationContext";
import { siteUrl } from "@library/utility/appUtils";

export interface IProps {
    themeID?: string | number;
    variables?: IThemeVariables;
    getSendMessage: (sendMessage: (message: {}) => void) => void;
}

export default function ThemeEditor(props: IProps) {
    const classes = themeEditorClasses();
    const { iframeRef, setIFrameRef, sendMessage } = useIFrameCommunication();

    return (
        <>
            <button
                style={{
                    padding: "40px",
                    background: "white",
                    position: "relative",
                    color: "#000",
                    zIndex: 100000,
                }}
                type={"button"}
                onClick={() => {
                    sendMessage && sendMessage({ hello: "world" });
                }}
            >
                Send message
            </button>
            <div className={classes.wrapper}>
                <div className={classes.frame}>
                    <iframe
                        ref={setIFrameRef}
                        src={siteUrl("/theme/theme-settings/preview")}
                        width="100%"
                        height="100%"
                        scrolling="yes"
                    ></iframe>
                </div>
                <div className={classes.styleOptions}>
                    <ThemeBuilderForm variables={props.variables} />
                </div>
            </div>
        </>
    );
}
