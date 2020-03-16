/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useState, useEffect, useRef, useDebugValue, RefObject } from "react";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";

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
type IReceiveMessage = (message: MessageEvent) => void;

export function useOwnFrameMessages(onMessage: IReceiveMessage) {
    useEffect(() => {
        const handler: IReceiveMessage = e => {
            if (e.origin !== window.origin) {
                return;
            }

            if (e.data?.source !== "vanilla") {
                return;
            }

            onMessage(e);
        };
        window.addEventListener("message", handler, false);

        return () => {
            window.removeEventListener("message", handler, false);
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
                    contentWindow.postMessage({ source: "vanilla", ...message }, window.origin);
                } else {
                    throw new Error("Unsable to find iFrame");
                }
            };

            // Callback required because react set states will call callbacks.
            setSendMessage(() => realSendMessage);
        }
    }, [iframeRef, setIFrameRef, sendMessage, setSendMessage]);

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
