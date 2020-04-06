/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import { t } from "@vanilla/i18n";
import { useParams } from "react-router";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { useHistory } from "react-router-dom";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { useDeliveries } from "@webhooks/DeliveryHooks";
import { EmptyDeliveriesResults } from "../EmptyDeliveriesResults";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { IDeliveryFragment } from "@webhooks/DeliveryTypes";
import { DeliveryTableRow } from "@webhooks/DeliveryTableRow";

export default function DeliveryIndex() {
    const isLoading = status === LoadStatus.LOADING;
    const history = useHistory();
    const params = useParams<{ webhookID?: string }>();
    const { HeadItem } = DashboardTable;

    const deliveries = useDeliveries(!!parseInt(params.webhookID) ? parseInt(params.webhookID) : null);

    if (!deliveries.data) {
        return <Loader />;
    }

    return (
        <>
            <DashboardHeaderBlock title={t("Recent Deliveries")} showBackLink={true} />
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>{t("Delivery ID")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Date")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Duration")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Status")}</HeadItem>
                    </tr>
                }
                body={Object.values(deliveries.data).map((delivery: IDeliveryFragment) => (
                    <DeliveryTableRow key={delivery.webhookDeliveryID} delivery={delivery} />
                ))}
            />
            {deliveries.status === LoadStatus.SUCCESS &&
                deliveries.data !== undefined &&
                Object.entries(deliveries.data).length === 0 && <EmptyDeliveriesResults />}
        </>
    );
}
