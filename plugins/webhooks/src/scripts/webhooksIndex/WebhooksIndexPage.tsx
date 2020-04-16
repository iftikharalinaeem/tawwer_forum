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
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import { BrowserRouter } from "react-router-dom";

interface IOwnProps extends RouteComponentProps<{}> {}

export default function WebhooksIndexPage(props: IOwnProps) {
    const { HeadItem } = DashboardTable;
    const [editingID, setEditingID] = useState<number | null>(null);
    const [deleteID, setDeleteID] = useState<number | null>(null);
    const [isDelete, setIsDelete] = useState(false);

    const webhooks = useWebhooks();
    const helpAsset = (
        <DashboardHelpAsset>
            <h3>{t("Webhooks")}</h3>
            <p>{t("Welcome to Vanilla's Webhooks.")}</p>
            <h3>{t("Need More Help?")}</h3>
            <p>
                <SmartLink to={"https://success.vanillaforums.com/kb/categories/50-features"}>
                    {t("Webhooks")}
                </SmartLink>
            </p>
        </DashboardHelpAsset>
    );
    if (!webhooks.data) {
        return <Loader />;
    }

    return (
        <>
            <BrowserRouter>{helpAsset}</BrowserRouter>
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
