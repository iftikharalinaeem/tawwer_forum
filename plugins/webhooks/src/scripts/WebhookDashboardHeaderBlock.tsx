/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { LeftChevronCompactIcon } from "@library/icons/common";

interface IProps {
    showBackLink?: boolean;
    title: string;
    actionButtons?: React.ReactNode;
    onBack: () => void;
}

export function WebhookDashboardHeaderBlock(props: IProps) {
    return (
        <header className="header-block">
            <div className="title-block">
                {props.showBackLink && (
                    <Button baseClass={ButtonTypes.ICON} aria-label="Return" onClick={props.onBack}>
                        <LeftChevronCompactIcon />
                    </Button>
                )}
                <h1>{props.title}</h1>
            </div>
            {props.actionButtons}
        </header>
    );
}
export default WebhookDashboardHeaderBlock;
