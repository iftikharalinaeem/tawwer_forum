/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import { t } from "@vanilla/i18n";
import { useParams } from "react-router";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { useWebhookActions } from "@webhooks/WebhookActions";
import { EventType, WebhookStatus } from "@webhooks/WebhookTypes";
import { useWebhookData } from "@webhooks/WebhookHooks";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import Loader from "@library/loaders/Loader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { webhookAddEditClasses } from "@webhooks/WebhookAddEditStyles";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { useHistory } from "react-router-dom";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";

function WebhookAddEdit() {
    const { form, formSubmit } = useWebhookData();
    const { updateForm, initForm, saveWebhookForm, clearError, clearForm } = useWebhookActions();
    const params = useParams<{ webhookID?: string }>();
    const [submittedWebhookID, setSubmittedWebhookID] = useState<null | number>(null);
    const webhookID = params.webhookID ? params.webhookID : null;
    const isEditing = !!webhookID;
    const isLoading = status === LoadStatus.LOADING;
    const history = useHistory();
    const errors = formSubmit.error?.response.data?.errors;
    const webhookCSSClasses = webhookAddEditClasses();
    const checkRadioCSSClasses = checkRadioClasses();

    const handleIndividualEvents = function(isChecked: boolean, event: string) {
        let events = form.events;
        if (isChecked) {
            events.push(event);
        } else {
            events.splice(events.indexOf(event, 1));
        }

        console.log(events);
        updateForm({ events: events });
    };

    useEffect(() => {
        initForm(webhookID ? parseInt(webhookID) : undefined);
    }, [webhookID, initForm]);

    useEffect(() => {
        if (formSubmit.status === LoadStatus.SUCCESS && submittedWebhookID) {
            clearForm({ webhookID: submittedWebhookID });
            clearError();
            history.push("/webhook-settings");
        }
    }, [clearError, formSubmit, clearForm, submittedWebhookID, history]);

    if (form.error) {
        return <ErrorPage apiError={form.error} />;
    }

    if (isLoading || (isEditing && form.formStatus !== LoadStatus.SUCCESS)) {
        return <Loader />;
    }

    return (
        <>
            <form
                onSubmit={async event => {
                    event.preventDefault();
                    event.stopPropagation();
                    const submittedWebhook = await saveWebhookForm(form);
                    if (submittedWebhook.webhookID) {
                        setSubmittedWebhookID(submittedWebhook.webhookID);
                    }
                }}
            >
                <DashboardHeaderBlock title={isEditing ? t("Edit Webhook") : t("Add Webhook")} showBackLink={true} />
                <DashboardFormGroup label={t("Name")}>
                    <DashboardInput
                        errors={errors?.["name"]}
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
                        errors={errors?.["url"]}
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
                        errors={errors?.["secret"]}
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
                        errors={errors?.["events"]}
                        value={form.events.includes(EventType.ALL) ? EventType.ALL : EventType.INDIVIDUAL}
                        onChange={event => {
                            if (event === EventType.INDIVIDUAL) {
                                updateForm({ events: [] });
                            } else {
                                updateForm({ events: [event] });
                            }
                        }}
                    >
                        <DashboardRadioButton
                            className={webhookCSSClasses.eventRadio}
                            label={"Send all events to the webhook."}
                            value={EventType.ALL}
                            name={EventType.ALL}
                            disabled={isLoading}
                        />
                        <DashboardRadioButton
                            className={webhookCSSClasses.eventRadio}
                            label={"Select individual events."}
                            value={EventType.INDIVIDUAL}
                            name={EventType.INDIVIDUAL}
                            disabled={isLoading}
                        />
                        <div className={`${checkRadioCSSClasses.grid} ${webhookCSSClasses.pullRight}`}>
                            <DashboardCheckBox
                                label={"Comments"}
                                className={webhookCSSClasses.eventRadio}
                                checked={form.events.includes(EventType.COMMENT)}
                                disabled={form.events.includes(EventType.ALL)}
                                onChange={isChecked => handleIndividualEvents(isChecked, EventType.COMMENT)}
                            />
                            <DashboardCheckBox
                                label={"Discussions"}
                                className={webhookCSSClasses.eventRadio}
                                checked={form.events.includes(EventType.DISCUSSION)}
                                disabled={form.events.includes(EventType.ALL)}
                                onChange={isChecked => handleIndividualEvents(isChecked, EventType.DISCUSSION)}
                            />
                            <DashboardCheckBox
                                label={"Notifications"}
                                className={webhookCSSClasses.eventRadio}
                                checked={form.events.includes(EventType.NOTIFICATION)}
                                disabled={form.events.includes(EventType.ALL)}
                                onChange={isChecked => handleIndividualEvents(isChecked, EventType.NOTIFICATION)}
                            />
                            <DashboardCheckBox
                                label={"Users"}
                                className={webhookCSSClasses.eventRadio}
                                checked={form.events.includes(EventType.USER)}
                                disabled={form.events.includes(EventType.ALL)}
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
                        checked={form.status === WebhookStatus.ACTIVE}
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
