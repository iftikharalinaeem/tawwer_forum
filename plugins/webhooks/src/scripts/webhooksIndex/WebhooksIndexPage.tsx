/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
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
import { useHistory, RouteComponentProps, withRouter } from "react-router-dom";

registerReducer("webhooks", WebhookReducer);

interface IOwnProps extends RouteComponentProps<{}> {}

function WebhooksIndexPage(props: IOwnProps) {
    const { HeadItem } = DashboardTable;
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingID, setEditingID] = useState<number | null>(null);
    const [statusChangeID, setStatusChangeID] = useState<number | null>(null);
    const [purgeID, setPurgeID] = useState<number | null>(null);
    const params = useParams<{
       
    }>();
    const history = useHistory();
 
    const webhooks = useWebhooks();
    const closeForm = () => {
        setIsFormOpen(false);
        setEditingID(null);
    };
    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    if (!webhooks.data) {
        return <Loader />;
    }

    const { webhookID } = props;
    const isEditing = webhookID != null;

    useEffect(() => {
            if (isEditing) {
                props.history.push(`/webhook-settings/edit/${webhookID}/edit`);
            }

        // return () => {
        //     history.push('/webhook-settings');
        // }
     
    }, [isEditing, history, webhookID]);

    if (isFormOpen) {
       // alert('here2');
        return (
            <>
                <WebhookDashboardHeaderBlock
                    title={t("Add Webhook")}
                    showBackLink={true}
                    onBack={() => {
                        props.history.push("/webhook-settings");
                    }}
                />
                <WebhookAddEdit 
                webhookID={editingID ?? undefined}
                onClose={() => {
                    closeForm();
                    toggleButtonRef.current?.focus();  
                }}
                />
            </>
        );
    }
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
                            props.history.push('/webhook-settings/add');
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
