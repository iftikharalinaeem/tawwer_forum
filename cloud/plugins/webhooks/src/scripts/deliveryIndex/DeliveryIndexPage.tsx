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
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { useDeliveryData } from "@webhooks/DeliveryHooks";
import { EmptyDeliveriesResults } from "../EmptyDeliveriesResults";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { IDeliveryFragment } from "@webhooks/DeliveryTypes";
import { DeliveryAccordion } from "@webhooks/DeliveryAccordion";
import { useHistory } from "react-router-dom";
import { useDeliveryActions } from "@webhooks/DeliveryActions";
import { DeliveryAccordionCSSClasses } from "@webhooks/DeliveryAccordionStyles";

export default function DeliveryIndex() {
    const params = useParams<{ webhookID?: string }>();
    const { getAll } = useDeliveryActions();
    const { HeadItem } = DashboardTable;
    const { deliveriesByWebhookID } = useDeliveryData();
    const history = useHistory();
    const [isLoading, setIsLoading] = useState<string>(LoadStatus.PENDING);
    const DeliveryAccordionClasses = DeliveryAccordionCSSClasses();

    useEffect(() => {
        if (isLoading === LoadStatus.PENDING && typeof params.webhookID === "string") {
            getAll(parseInt(params.webhookID));
        }
    }, [getAll, params, isLoading]);

    if (!deliveriesByWebhookID.data) {
        return <Loader />;
    }

    return (
        <>
            <DashboardHeaderBlock
                title={t("Recent Deliveries")}
                showBackLink={true}
                onBackClick={() => {
                    setIsLoading(LoadStatus.PENDING);
                    history.push("/webhook-settings");
                }}
            />
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>{t("Delivery ID")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Date")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Duration")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Status")}</HeadItem>
                    </tr>
                }
                body={Object.values(deliveriesByWebhookID.data).map((delivery: IDeliveryFragment, index: number) => {
                    return (
                        <tr key={delivery.webhookDeliveryID}>
                            <td colSpan={4} className={DeliveryAccordionClasses.rowWrap}>
                                <DeliveryAccordion key={delivery.webhookDeliveryID} delivery={delivery} index={index} />
                            </td>
                        </tr>
                    );
                })}
            />

            {deliveriesByWebhookID.status === LoadStatus.SUCCESS &&
                deliveriesByWebhookID.data !== undefined &&
                Object.entries(deliveriesByWebhookID.data).length === 0 && <EmptyDeliveriesResults />}
        </>
    );
}
