/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";

interface IProps {
    webhookID?: number;
    onBack?: () => void;
}

export function WebhookAddEdit(props: IProps) {
    const onBack = () => {
        if (props.onBack) props.onBack();
    };

    return <>hello from the form</>;
}
