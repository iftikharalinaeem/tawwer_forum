import React from "react";

interface IProps {
    webhookID?: number;
    onBack?: () => void;
}

export function WebhookAddEdit(props: IProps) {
    const onBack = () => {
        alert(1);
        props.onBack();
    };

    return <>hello from the form</>;
}
