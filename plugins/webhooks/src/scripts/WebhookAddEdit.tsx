/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@vanilla/i18n";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { useWebhookData, WebhooksEventsSelect } from "@webhooks/WebhookTypes";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";

interface IProps {
    webhookID?: number;
    onBack?: () => void;
}

export function WebhookAddEdit(props: IProps) {
    const { form, formSubmit } = useWebhookData();
    const isLoading = formSubmit.status === LoadStatus.LOADING;
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
                <DashboardFormGroup label={t("Name")}>
                    <DashboardInput
                        inputProps={{
                            value: form.name,
                        }}
                    />
                </DashboardFormGroup>
                <DashboardFormGroup label={t("Delivery Url")}>
                    <DashboardInput
                        inputProps={{
                            placeholder: "https://",
                            value: form.url,
                        }}
                    />
                </DashboardFormGroup>
                <DashboardFormGroup label={t("Secret")} description={t("The Secret is used to sign each delivery.")}>
                    <DashboardInput
                        inputProps={{
                            value: form.secret,
                        }}
                    />
                </DashboardFormGroup>
                <DashboardFormGroup label={t("Which events should trigger this webhook?")}>
                    <DashboardRadioGroup
                        value={form.events}
                        onChange={events => {
                        }}
                    >
                        <DashboardRadioButton
                            label={"Send all events to the webhook."}
                            value={WebhooksEventsSelect.ALL}
                            name={WebhooksEventsSelect.ALL}
                            disabled={isLoading}
                        />
                        <DashboardRadioButton
                            label={"Select individual events."}
                            value={WebhooksEventsSelect.INDIVIDUAL}
                            name={WebhooksEventsSelect.INDIVIDUAL}
                            disabled={isLoading}
                        />
                    </DashboardRadioGroup>
                </DashboardFormGroup>
                <DashboardFormGroup 
                    label={t("Active")}
                    description={t("Whether or not events will be delivered to this webhook.")}
                    labelType={DashboardLabelType.WIDE}
                >
                    <DashboardToggle
                        onChange={() => {}}
                        checked={true}
                    />
                </DashboardFormGroup>
                <div className="Buttons form-footer">
                    <Button
                        submit={true}
                        baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                        disabled={isLoading}
                    >
                        {isLoading ? <ButtonLoader /> : t("Save")}
                    </Button>
                </div>
            </form>
        </>
    );
}
