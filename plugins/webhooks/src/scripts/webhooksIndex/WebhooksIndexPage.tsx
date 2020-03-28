/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import { useParams } from "react-router";
import LinkAsButton from "@library/routing/LinkAsButton";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { t } from "@vanilla/i18n";
import { WebhookStatus } from "@webhooks/WebhookTypes";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { WebhooksTableRow } from "@webhooks/WebhooksTableRow";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Loader from "@library/loaders/Loader";
import { useWebhooks } from "@webhooks/WebhookHooks";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { EmptyWebhooksResults } from "@webhooks/EmptyWebhooksResults";
import { LoadStatus } from "@library/@types/api/core";
import { useHistory, RouteComponentProps, withRouter } from "react-router-dom";
import { WebhookDeleteModal } from "@webhooks/WebhookDeleteModal";

interface IOwnProps extends RouteComponentProps<{}> {}

function WebhooksIndexPage(props: IOwnProps) {
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
                body={Object.values(webhooks.data).map(webhook => (
                    <WebhooksTableRow
                        key={webhook.webhookID}
                        webhook={webhook}
                        onEditClick={() => {
                            setEditingID(webhook.webhookID);
                        }}
                        onDeleteClick={() => {
                            setIsDelete(true);
                            setDeleteID(webhook.webhookID);
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

export default withRouter(WebhooksIndexPage);
