/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import ReactDOM from "react-dom";
import TabHandler from "@library/TabHandler";
import { logError } from "@library/utility";

interface IProps {
    onClose: () => void;
    appContainer: Element;
    container: Element;
    children: React.ReactNode;
}

export default class Modal extends React.Component<IProps> {
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();

    public render() {
        return ReactDOM.createPortal(
            <div className="modal inheritHeight" ref={this.selfRef} onKeyDown={this.selfKeyDownHandler}>
                {this.props.children}
            </div>,
            this.props.container,
        );
    }

    private get tabHandler(): TabHandler {
        return new TabHandler(this.selfRef.current!);
    }

    public componentDidMount() {
        this.focusInitialElement();
        document.addEventListener("keydown", this.globalKeyDownHandler);
        this.props.appContainer.setAttribute("aria-hidden", true);
        document.body.style.position = "fixed";
    }

    public componentWillUnmount() {
        this.props.appContainer.removeAttribute("aria-hidden");
        document.removeEventListener("keydown", this.globalKeyDownHandler);
        document.body.style.position = "initial";
    }

    private focusInitialElement() {
        const initialElement = this.tabHandler.getInitial();
        if (initialElement) {
            initialElement.focus();
        } else {
            logError("A modal was created without any focusable element");
        }
    }

    private selfKeyDownHandler = (event: React.KeyboardEvent) => {
        const tabKey = 9;

        if (event.shiftKey && event.keyCode === tabKey) {
            this.handleShiftTab(event);
        } else if (!event.shiftKey && event.keyCode === tabKey) {
            this.handleTab(event);
        }
    };

    private globalKeyDownHandler = (event: KeyboardEvent) => {
        const escKey = 27;

        if (event.keyCode === escKey) {
            event.preventDefault();
            event.stopImmediatePropagation();
            this.props.onClose();
        }
    };

    private handleShiftTab(event: React.KeyboardEvent) {
        const nextElement = this.tabHandler.getNext(undefined, true);
        if (nextElement) {
            event.preventDefault();
            event.stopPropagation();
            nextElement.focus();
        }
    }

    private handleTab(event: React.KeyboardEvent) {
        const previousElement = this.tabHandler.getNext();
        if (previousElement) {
            event.preventDefault();
            event.stopPropagation();
            previousElement.focus();
        }
    }
}
