/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { useParams } from "react-router";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { t } from "@vanilla/i18n";
import { WebhookStatus } from "@webhooks/WebhookTypes";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { WebhooksTableRow } from "@webhooks/WebhooksTableRow";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Loader from "@library/loaders/Loader";
import { useWebhooks } from "@webhooks/WebhookHooks";
import { WebhookReducer } from "@webhooks/WebhookReducer";
import { registerReducer } from "@library/redux/reducerRegistry";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { EmptyWebhooksResults } from "@webhooks/EmptyWebhooksResults";
import { LoadStatus } from "@library/@types/api/core";
import { WebhookAddEdit } from "@webhooks/WebhookAddEdit";
import { WebhookDashboardHeaderBlock } from "@webhooks/WebhookDashboardHeaderBlock";
import { useHistory, RouteComponentProps, withRouter } from 'react-router-dom';

registerReducer("webhooks", WebhookReducer);

interface IOwnProps extends RouteComponentProps<{}> {
}

function WebhooksIndexPage(props: IOwnProps) {
    const { HeadItem } = DashboardTable;
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingID, setEditingID] = useState<number | null>(null);
    const [statusChangeID, setStatusChangeID] = useState<number | null>(null);
    const [purgeID, setPurgeID] = useState<number | null>(null);
    const params = useParams<{
        // Types of the params from your route match.
        // All parameters come from query so they will be strings.
        // Be sure to convert numbers/booleans/etc.
    }>();
    const history = useHistory()

    const webhooks = useWebhooks();
    const closeForm = () => {
        setIsFormOpen(false);
    };
    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    if (!webhooks.data) {
        return <Loader />;
    }

    //if (isFormOpen) {
        //props.history.push("/webhook-settings/add");
        return (
            <>
          
                <WebhookDashboardHeaderBlock
                    title={t("Add Webhook")}
                    showBackLink={true}
                    onBack={() => {
                        //props.history.push("/webhook-settings/back");
                    }}
                />
                <WebhookAddEdit />
            </>
        );
    //}

    return (
        <>
            <DashboardHeaderBlock
                title={t("Webhooks")}
                actionButtons={
                    <Button
                        buttonRef={toggleButtonRef}
                        baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                        onClick={() => {
                            setIsFormOpen(true);
                        }}
                    >
                        {t("Add Webhook")}
                    </Button>
                }
            />
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>Webhooks</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Status")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Options")}</HeadItem>
                    </tr>
                }
                body={Object.values(webhooks.data).map(webhook => (
                    <WebhooksTableRow
                        key={webhook.webhookID}
                        webhook={webhook}
                        onEditClick={
                            status === WebhookStatus.ACTIVE
                                ? () => {
                                      setEditingID(webhook.webhookID);
                                      setIsFormOpen(true);
                                  }
                                : undefined
                        }
                        onStatusChangeClick={() => {
                            setStatusChangeID(webhook.webhookID);
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
