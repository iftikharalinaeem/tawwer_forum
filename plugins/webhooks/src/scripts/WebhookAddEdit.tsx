/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@vanilla/i18n";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { useWebhookActions } from "@webhooks/WebhookActions";
import { useWebhookData, WebhookEvents, WebhookStatus } from "@webhooks/WebhookTypes";
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
    const { updateForm } = useWebhookActions();
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
                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                const { value } = event.target;
                                updateForm({ name: value });
                            },
                            value: form.name,
                        }}
                    />
                </DashboardFormGroup>
                <DashboardFormGroup label={t("Delivery Url")}>
                    <DashboardInput
                        inputProps={{
                            placeholder: "https://",
                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                const { value } = event.target;
                                updateForm({ url: value });
                            },
                            value: form.url,
                        }}
                    />
                </DashboardFormGroup>
                <DashboardFormGroup label={t("Secret")} description={t("The Secret is used to sign each delivery.")}>
                    <DashboardInput
                        inputProps={{
                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                const { value } = event.target;
                                updateForm({ secret: value });
                            },
                            value: form.secret,
                        }}
                    />
                </DashboardFormGroup>
                <DashboardFormGroup label={t("Which events should trigger this webhook?")}>
                    <DashboardRadioGroup
                        value={form.events}
                        onChange={events => {
                            updateForm({ events: events as WebhookEvents });
                        }}
                    >
                        <DashboardRadioButton
                            label={"Send all events to the webhook."}
                            value={WebhookEvents.ALL}
                            name={WebhookEvents.ALL}
                            disabled={isLoading}
                        />
                        <DashboardRadioButton
                            label={"Select individual events."}
                            value={WebhookEvents.INDIVIDUAL}
                            name={WebhookEvents.INDIVIDUAL}
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
                        onChange={(isToggled) => {
                            console.log(isToggled);
                            updateForm({ status: (isToggled ? WebhookStatus.ACTIVE : WebhookStatus.DISABLED) });
                        }}
                        checked={form.status}
                        disabled={isLoading}
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
