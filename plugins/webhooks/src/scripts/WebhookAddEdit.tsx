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
import { useWebhookData, EventType, WebhookStatus } from "@webhooks/WebhookTypes";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { webhookAddEditClasses } from "@webhooks/WebhookAddEditStyles"; 

interface IProps {
    webhookID?: number;
    onBack?: () => void;
}

export function WebhookAddEdit(props: IProps) {
    const { form, formSubmit } = useWebhookData();
    const { updateForm } = useWebhookActions();
    const isLoading = formSubmit.status === LoadStatus.LOADING;
    const webhookCSSClasses = webhookAddEditClasses();
    const EventTypes = [
        {
            label: t("Comments"),
            value: EventType.COMMENT
        },
        {
            label: t("Discussions"),
            value: EventType.DISCUSSION
        },
        {
            label: t("Users"),
            value: EventType.USER
        }
    ];
    const onBack = () => {
        if (props.onBack) props.onBack();
    };

    const handleIndividualEvents = function (isChecked: boolean, event: string) {
        let events = JSON.parse(form.events);
        if( isChecked) {
            events.push(event);
        } else {
            events.splice(events.indexOf(event, 1));
        }

        updateForm({ events: JSON.stringify(events) });
    };

    return (
        <>
            <form
                onSubmit={async event => {
                    event.preventDefault();
                    //void saveKbForm();
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
                        value={JSON.parse(form.events).includes(EventType.ALL) ? EventType.ALL : EventType.INDIVIDUAL}
                        onChange={(event) => {
                            if (event === EventType.INDIVIDUAL) {
                                updateForm({events: JSON.stringify([])});
                            } else {
                                updateForm({events: JSON.stringify([event])});
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
                                onChange={(isChecked) => handleIndividualEvents(isChecked, EventType.COMMENT)}
                            />
                            <DashboardCheckBox
                                label={"Discussions"}
                                className={webhookCSSClasses.inlineCheckbox}
                                checked={form.events.includes(EventType.DISCUSSION) ? true : false}
                                disabled={form.events.includes(EventType.ALL) ? true : false}
                                onChange={(isChecked) => handleIndividualEvents(isChecked, EventType.DISCUSSION)}
                            />
                            <DashboardCheckBox
                                label={"Users"}
                                className={webhookCSSClasses.inlineCheckbox}
                                checked={form.events.includes(EventType.USER) ? true : false}
                                disabled={form.events.includes(EventType.ALL) ? true : false}
                                onChange={(isChecked) => handleIndividualEvents(isChecked, EventType.USER)}
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
                        onChange={(isToggled) => {
                            updateForm({ status: isToggled ? WebhookStatus.ACTIVE : WebhookStatus.DISABLED });
                        }}
                        checked={(form.status === WebhookStatus.ACTIVE ? true : false)}
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
