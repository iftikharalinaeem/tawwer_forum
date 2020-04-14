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
import { DeliveryTableRow } from "@webhooks/DeliveryTableRow";
import { useHistory } from "react-router-dom";
import { useDeliveryActions } from "@webhooks/DeliveryActions";
import classNames from "classnames";

export default function DeliveryIndex() {
    const params = useParams<{ webhookID?: string }>();
    const { getAll } = useDeliveryActions();
    const { HeadItem } = DashboardTable;
    const { deliveriesByWebhookID } = useDeliveryData();
    const history = useHistory();
    const [isLoading, setIsLoading] = useState<string>(LoadStatus.PENDING);
    const [activeTab, setActiveTab] = useState(-1);
    const [cachedActiveTab, setCachedActiveTab] = useState(-1);

    const getCollapseDeliveryButton = function(target): Element {
        let collapseDeliveryButton = target.parentElement.classList.contains("collapseDeliveryButton")
            ? target.parentElement
            : target.firstChild.classList.contains("collapseDeliveryButton")
            ? target.firstChild
            : target;

        return collapseDeliveryButton;
    };

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
                    let isActive = activeTab === index;

                    return (
                        <tr key={delivery.webhookDeliveryID} className={classNames(isActive ? "isOpen" : "")}>
                            <DeliveryTableRow
                                delivery={delivery}
                                isActive={isActive}
                                index={index}
                                onClick={e => {
                                    e.preventDefault();
                                    const collapseDeliveryButton = getCollapseDeliveryButton(e.target);
                                    isActive = collapseDeliveryButton.dataset.index !== activeTab;
                                    setActiveTab(index);
                                }}
                            />
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
