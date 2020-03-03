/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useState, useEffect, useRef, useDebugValue, RefObject } from "react";

interface IContextValue {
    iframeRef: HTMLIFrameElement | null;
    setIFrameRef: (iframe: HTMLIFrameElement) => void;
    sendMessage: ISendMessage | null;
}

const context = React.createContext<IContextValue>({
    iframeRef: null,
    setIFrameRef(iframe: HTMLIFrameElement) {
        return;
    },
    sendMessage: () => {},
});

export function useIFrameCommunication() {
    const value = useContext(context);
    useDebugValue(value);
    return value;
}
type ISendMessage = (message: Record<string, any>) => void;

export function useOwnFrameMessages(onMessage: ISendMessage) {
    useEffect(() => {
        window.addEventListener("message", onMessage, false);

        return () => {
            window.removeEventListener("message", onMessage, false);
        };
    }, [onMessage]);
}

export function IframeCommunicationContextProvider(props: { children: React.ReactNode }) {
    const [iframeRef, setIFrameRef] = useState<HTMLIFrameElement | null>(null);
    const [sendMessage, setSendMessage] = useState<null | ISendMessage>(null);

    useEffect(() => {
        if (iframeRef?.contentWindow && !sendMessage) {
            // Add event listenners
            const contentWindow = iframeRef.contentWindow;

            const realSendMessage = function(message: Record<string, any>) {
                if (contentWindow) {
                    contentWindow.postMessage(message, window.origin);
                } else {
                    throw new Error("Unsable to find iFrame");
                }
            };

            // Callback required because react set states will call callbacks.
            setSendMessage(() => realSendMessage);
        }

        return () => {
            setIFrameRef(null);
            setSendMessage(null);
        };
    }, [iframeRef, setSendMessage, setIFrameRef, sendMessage]);

    return (
        <context.Provider
            value={{
                iframeRef,
                setIFrameRef,
                sendMessage,
            }}
        >
            {props.children}
        </context.Provider>
    );
}
