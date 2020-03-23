/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, {useState} from "react";
import { useParams } from "react-router";
import { BrowserRouter } from "react-router-dom";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { t } from "@vanilla/i18n";
import { WebhookStatus } from "@webhooks/WebhookModel";
import qs from "qs";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { WebhooksTableRow } from "@webhooks/WebhooksTableRow";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Loader from "@library/loaders/Loader";
import { useWebhooks } from "@webhooks/WebhookHooks";
import { WebhookReducer } from "@webhooks/WebhookReducer";
import { registerReducer } from "@library/redux/reducerRegistry";
import {DashboardTable} from "@dashboard/tables/DashboardTable";
import { useWebhookActions } from "@webhooks/WebhookActions";
const { HeadItem } = DashboardTable;

registerReducer("webhooks", WebhookReducer);

export default function PlaceHolderPage() {
    const initialForm = qs.parse(window.location.search.replace(/^\?/, ""));
    const status = initialForm.status || WebhookStatus.ACTIVE;
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingID, setEditingID] = useState<number | null>(null);
    const [statusChangeID, setStatusChangeID] = useState<number | null>(null);
    const [purgeID, setPurgeID] = useState<number | null>(null);
    const params = useParams<{
        // Types of the params from your route match.
        // All parameters come from query so they will be strings.
        // Be sure to convert numbers/booleans/etc.
    }>();

    const webhooks = useWebhooks();
    const toggleButtonRef = React.createRef<HTMLButtonElement>();
    if (!webhooks.data) {
        return <Loader />;
    }

    return (
        <BrowserRouter>
            <DashboardHeaderBlock
                title={t("Webhooks")}
                actionButtons={
                    <Button
                        buttonRef={toggleButtonRef}
                        baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                    >
                        {t("Add Webhook")}
                    </Button>
                }
            />
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>Webhooks</HeadItem>
                        <HeadItem>Status</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>Options</HeadItem>
                    </tr>
                }

                body={Object.values(webhooks.data)
                    .filter(webhook => webhook.status === status)
                    .map(webhook => (
                        <WebhooksTableRow
                            key={webhook.webhookID}
                            webhook={webhook}
                            forStatus={status}
                            onEditClick={
                                status === WebhookStatus.ACTIVE
                                    ? () => {
                                        setEditingID(webhook.webhookID);
                                        setIsFormOpen(true);
                                    }
                                    : undefined
                            }
                            onPurgeClick={
                                status === WebhookStatus.DISABLED
                                    ? () => {
                                        setPurgeID(webhook.webhookID);
                                    }
                                    : undefined
                            }
                            onStatusChangeClick={() => {
                                setStatusChangeID(webhook.webhookID);
                            }}
                        />
                    ))}
            />
        </BrowserRouter>
    );
}
