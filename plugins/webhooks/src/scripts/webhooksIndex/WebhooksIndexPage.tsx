/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import LinkAsButton from "@library/routing/LinkAsButton";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { WebhooksTableRow } from "@webhooks/WebhooksTableRow";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Loader from "@library/loaders/Loader";
import { useWebhooks } from "@webhooks/WebhookHooks";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { EmptyWebhooksResults } from "@webhooks/EmptyWebhooksResults";
import { LoadStatus } from "@library/@types/api/core";
import { RouteComponentProps } from "react-router-dom";
import { WebhookDeleteModal } from "@webhooks/WebhookDeleteModal";
import { IWebhook } from "@webhooks/WebhookTypes";

interface IOwnProps extends RouteComponentProps<{}> {}

export default function WebhooksIndexPage(props: IOwnProps) {
    const { HeadItem } = DashboardTable;
    const [editingID, setEditingID] = useState<number | null>(null);
    const [deleteID, setDeleteID] = useState<number | null>(null);
    const [isDelete, setIsDelete] = useState(false);

    const webhooks = useWebhooks();

    if (!webhooks.data) {
        return <Loader />;
    }

    return (
        <>
            <DashboardHeaderBlock
                title={t("Webhooks")}
                actionButtons={
                    <LinkAsButton baseClass={ButtonTypes.DASHBOARD_PRIMARY} to={"/webhook-settings/add"}>
                        {t("Add Webhook")}
                    </LinkAsButton>
                }
            />
            {isDelete && (
                <WebhookDeleteModal
                    webhookID={deleteID}
                    onDismiss={() => {
                        setDeleteID(null);
                    }}
                />
            )}
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>{t("Webhooks")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Status")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Options")}</HeadItem>
                    </tr>
                }
                body={Object.values(webhooks.data).map((webhook: IWebhook) => (
                    <WebhooksTableRow
                        key={webhook.webhookID}
                        webhook={webhook}
                        onEditClick={() => {
                            if (webhook.webhookID) {
                                setEditingID(webhook.webhookID);
                            }
                        }}
                        onDeleteClick={() => {
                            setIsDelete(true);
                            if (webhook.webhookID) {
                                setIsDelete(true);
                                setDeleteID(webhook.webhookID);
                            }
                        }}
                    />
                ))}
            />
            {webhooks.status === LoadStatus.SUCCESS &&
                webhooks.data !== undefined &&
                Object.entries(webhooks.data).length === 0 && <EmptyWebhooksResults />}
        </>
    );
}
