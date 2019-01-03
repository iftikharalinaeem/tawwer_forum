/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Button from "@library/components/forms/Button";
import ButtonLoader from "@library/components/ButtonLoader";
import classNames from "classnames";

interface IProps {
    className?: string;
    title: string;
    text: string;
    isActionLoading?: boolean;
    actionHandler?: () => void;
    actionTitle?: string;
}

/**
 * A message with a title, text, and optional action button.
 */
export default class Message extends React.Component<IProps> {
    public render() {
        const { actionHandler, isActionLoading, title, text, actionTitle, className } = this.props;
        return (
            <div className={classNames("message", className)}>
                <div className="message-main">
                    <h2 className="message-title">{title}</h2>
                    <p className="message-text">{text}</p>
                </div>
                {actionTitle && (
                    <Button className="message-button buttonPrimary" onClick={actionHandler}>
                        {isActionLoading ? <ButtonLoader /> : actionTitle}
                    </Button>
                )}
            </div>
        );
    }
}
