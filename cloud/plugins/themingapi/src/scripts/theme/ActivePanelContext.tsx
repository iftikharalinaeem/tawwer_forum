/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useContext, useCallback } from "react";
import { useIFrameCommunication, useOwnFrameMessages } from "@themingapi/theme/IframeCommunicationContext";
import themeEditorPreviewClasses from "@themingapi/theme/ThemeEditorPreviewContents.styles";
import { ColorHelper } from "csx";
import { colorOut } from "@vanilla/library/src/scripts/styles/styleHelpers";
import classNames from "classnames";
import { actionCreatorFactory } from "typescript-fsa";

export enum ActiveVariablePanel {
    GLOBAL = "global",
    TITLE_BAR = "titleBar",
    BANNER = "banner",
    CONTENT_BANNER = "contentBanner",
}

const createAction = actionCreatorFactory("@@themeBuilderPanel");
export const setActivePanelAC = createAction<{ panel: ActiveVariablePanel }>("setActivePanel");
const activePanelContext = React.createContext({
    activePanel: ActiveVariablePanel.GLOBAL,
    setActivePanel: (activePanel: ActiveVariablePanel) => {},
});

export function useActivePanelContext() {
    return useContext(activePanelContext);
}

window.lastActivePanel = ActiveVariablePanel.GLOBAL;

export function ActivePanelContextProvider(props: { children: React.ReactNode; isPreview?: boolean }) {
    const { sendMessageOut, sendMessage } = useIFrameCommunication();
    const [activePanel, _setActivePanel] = useState(window.lastActivePanel);

    const setActivePanel = (activePanel: ActiveVariablePanel) => {
        // Stash on window to prevent losing data when component is reinitialized.
        window.lastActivePanel = activePanel;
        _setActivePanel(activePanel);

        const message = setActivePanelAC({ panel: activePanel });
        if (props.isPreview) {
            sendMessageOut?.(message);
        } else {
            sendMessage?.(message);
        }
    };

    const handler = useCallback(
        (e: MessageEvent) => {
            const { data } = e;
            if (e.data.type === setActivePanelAC.type) {
                window.lastActivePanel = data.payload.panel;
                _setActivePanel(data.payload.panel);
            }
        },
        [_setActivePanel],
    );

    useOwnFrameMessages(handler);

    return (
        <activePanelContext.Provider
            value={{
                activePanel,
                setActivePanel,
            }}
        >
            {props.children}
        </activePanelContext.Provider>
    );
}

export function PanelActivator(props: { panel: ActiveVariablePanel; children: React.ReactNode; color?: ColorHelper }) {
    const { activePanel, setActivePanel } = useActivePanelContext();
    const classes = themeEditorPreviewClasses();
    const { color } = props;
    return (
        <div
            className={classes.panelActivator}
            onClick={e => {
                e.stopPropagation();
                setActivePanel(props.panel);
            }}
        >
            {props.children}
            {props.panel === activePanel && (
                <div className={classNames(classes.panelActivatorIndicator(color ? colorOut(color) : undefined))}></div>
            )}
        </div>
    );
}
