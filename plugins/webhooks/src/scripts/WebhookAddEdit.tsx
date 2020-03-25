/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@vanilla/i18n";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { useWebhookData } from "@webhooks/WebhookTypes";

interface IProps {
    webhookID?: number;
    onBack?: () => void;
}

export function WebhookAddEdit(props: IProps) {
    const { form, formSubmit } = useWebhookData();
    const onBack = () => {
        if (props.onBack) props.onBack();
    };

    return (
        <>
            <form
                onSubmit={async event => {
                    event.preventDefault();
                }}
            >
                <DashboardFormGroup label="Name">
                    <DashboardInput
                        inputProps={{
                            value: form.name,
                        }}
                    />
                </DashboardFormGroup>
            </form>
        </>
    );
}
