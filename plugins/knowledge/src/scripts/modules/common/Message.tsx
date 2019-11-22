/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import classNames from "classnames";
import { buttonClasses } from "@library/forms/buttonStyles";
import { messagesClasses } from "@library/messages/messageStyles";

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
        const classes = messagesClasses();
        const { actionHandler, isActionLoading, title, text, actionTitle, className } = this.props;
        return (
            <div className={classNames(classes.root, className)}>
                <div className={classes.main}>
                    <h2 className={classes.title}>{title}</h2>
                    <p className={classes.text}>{text}</p>
                </div>
                {actionTitle && (
                    <Button className={classNames(classes.actionButton)} onClick={actionHandler}>
                        {isActionLoading ? <ButtonLoader /> : actionTitle}
                    </Button>
                )}
            </div>
        );
    }
}
