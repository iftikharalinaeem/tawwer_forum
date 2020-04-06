/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import { t } from "@vanilla/i18n";
import { useParams } from "react-router";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import Button from "@library/forms/Button";
import Loader from "@library/loaders/Loader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { useHistory } from "react-router-dom";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { useDeliveries } from "@webhooks/DeliveryHooks";
import { EmptyDeliveriesResults } from "../EmptyDeliveriesResults";

export default function DeliveryIndex() {
    //const isLoading = status === LoadStatus.LOADING;
    //const history = useHistory();
    //const params = useParams<{ webhookID?: string }>();
    //const { HeadItem } = DashboardTable;

    const deliveries = useDeliveries();

    if (!deliveries.data) {
        alert(JSON.stringify(deliveries, null, 4));
        return <Loader />;
    }

    return (
        <>
           
                <DashboardHeaderBlock title={t("Recent Deliveries")} showBackLink={true} />
                {/* <DashboardTable
                head={
                    <tr>
                        <HeadItem>{t("Delivery ID")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Date")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Duration")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Status")}</HeadItem>
                    </tr>
                }
                body={Object.values(delivery.data).map((delivery: IDelivery) => (
                    <DeliveryTableRow
                        key={delivery.webhookID}
                        delivery={delivery}
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
                /> */}

                    {/* <DeliveryTableRow/>
            /> */}
            {deliveries.status === LoadStatus.SUCCESS &&
                deliveries.data !== undefined &&
                Object.entries(deliveries.data).length === 0 && <EmptyDeliveriesResults />}
        </>
    );
}
