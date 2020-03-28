/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import { t } from "@vanilla/i18n";
import { useParams } from "react-router";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { useWebhookActions } from "@webhooks/WebhookActions";
import { EventType, WebhookStatus } from "@webhooks/WebhookTypes";
import { useWebhookData, useWebhooks } from "@webhooks/WebhookHooks";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";
import Loader from "@library/loaders/Loader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { webhookAddEditClasses } from "@webhooks/WebhookAddEditStyles";
import { WebhookDashboardHeaderBlock } from "@webhooks/WebhookDashboardHeaderBlock";
import { useHistory } from "react-router-dom";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";


export function WebhookAddEdit() {
    const { form, formSubmit } = useWebhookData();
    const { updateForm, initForm, saveWebhookForm, clearError } = useWebhookActions();
    const params = useParams<{webhookID?: number}>();
    const webhookID = params.webhookID ? params.webhookID : null;
    const isEditing = !!webhookID;
    const isLoading = status === LoadStatus.LOADING;
    const isFormSubmitSuccessful = formSubmit.status === LoadStatus.SUCCESS;
    const history = useHistory();
    const webhookCSSClasses = webhookAddEditClasses();

    const handleIndividualEvents = function(isChecked: boolean, event: string) {
        let events = form.events;
        if (isChecked) {
            events.push(event);
        } else {
            events.splice(events.indexOf(event, 1));
        }

        updateForm({ events: events });
    };

    useEffect(() => {
        initForm(webhookID);
    }, [webhookID, initForm]);

    useEffect(() => {
        if(isFormSubmitSuccessful) {
            clearError();
            history.push('/webhook-settings');
        }   
    });

    if (form.error) {
        return <ErrorPage apiError={form.error} />;
    }

    if (isLoading || (isEditing && form.formStatus !== LoadStatus.SUCCESS)) {
        return <Loader />
    }

    return (
        <>
            <form
                onSubmit={async event => {
                    event.preventDefault();
                    event.stopPropagation();
                    void saveWebhookForm(form);
                }}
            >
                <WebhookDashboardHeaderBlock
                    title={isEditing ? t("Edit Webhook") : t("Add Webhook")}
                    showBackLink={true}
                    onBack={() => {
                        history.push("/webhook-settings");
                    }}
                />
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
                        value={(form.events).includes(EventType.ALL) ? EventType.ALL : EventType.INDIVIDUAL}
                        onChange={event => {
                            if (event === EventType.INDIVIDUAL) {
                                updateForm({ events: [] });
                            } else {
                                updateForm({ events: [event] });
                            }
                        }}
                    >
                        <DashboardRadioButton
                            className={webhookCSSClasses.statusRadio}
                            label={"Send all events to the webhook."}
                            value={EventType.ALL}
                            name={EventType.ALL}
                            disabled={isLoading}
                        />
                        <DashboardRadioButton
                            className={webhookCSSClasses.statusRadio}
                            label={"Select individual events."}
                            value={EventType.INDIVIDUAL}
                            name={EventType.INDIVIDUAL}
                            disabled={isLoading}
                        />
                        <div className={webhookCSSClasses.inlinePullRight}>
                            <DashboardCheckBox
                                label={"Comments"}
                                className={webhookCSSClasses.inlineCheckbox}
                                checked={form.events.includes(EventType.COMMENT) ? true : false}
                                disabled={form.events.includes(EventType.ALL) ? true : false}
                                onChange={isChecked => handleIndividualEvents(isChecked, EventType.COMMENT)}
                            />
                            <DashboardCheckBox
                                label={"Discussions"}
                                className={webhookCSSClasses.inlineCheckbox}
                                checked={form.events.includes(EventType.DISCUSSION) ? true : false}
                                disabled={form.events.includes(EventType.ALL) ? true : false}
                                onChange={isChecked => handleIndividualEvents(isChecked, EventType.DISCUSSION)}
                            />
                            <DashboardCheckBox
                                label={"Users"}
                                className={webhookCSSClasses.inlineCheckbox}
                                checked={form.events.includes(EventType.USER) ? true : false}
                                disabled={form.events.includes(EventType.ALL) ? true : false}
                                onChange={isChecked => handleIndividualEvents(isChecked, EventType.USER)}
                            />
                        </div>
                    </DashboardRadioGroup>
                </DashboardFormGroup>
                <DashboardFormGroup
                    label={t("Active")}
                    description={t("Whether or not events will be delivered to this webhook.")}
                    labelType={DashboardLabelType.WIDE}
                >
                    <DashboardToggle
                        onChange={isToggled => {
                            updateForm({ status: isToggled ? WebhookStatus.ACTIVE : WebhookStatus.DISABLED });
                        }}
                        checked={form.status === WebhookStatus.ACTIVE ? true : false}
                    />
                </DashboardFormGroup>
                <div className="Buttons form-footer">
                    <Button submit={true} baseClass={ButtonTypes.DASHBOARD_PRIMARY} disabled={isLoading}>
                        {isLoading ? <ButtonLoader /> : t("Save")}
                    </Button>
                </div>
            </form>
        </>
    );
}
export default WebhookAddEdit;
