/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { IDeliveryFragment } from "@webhooks/DeliveryTypes";
import React from "react";
import moment from "moment";

interface IProps {
    delivery: IDeliveryFragment;
}

export function DeliveryTableRow(props: IProps) {
    const { delivery } = props;
    const durationToSeconds = function (duration: number) {
        let seconds = duration / 1000;
        return seconds + "s";
    };
    return (
        <tr>
            <td>
                <DashboardMediaItem title={delivery.webhookDeliveryID} info="" />
            </td>
            <td>
                <DashboardMediaItem
                    title={moment(new Date(delivery.dateInserted)).format("YYYY-MM-DD hh:mm")}
                    info=""
                />
            </td>
            <td>
                <DashboardMediaItem title={durationToSeconds(delivery.requestDuration)} info="" />
            </td>
            <td>
                <DashboardMediaItem title={String(delivery.responseCode)} info="" />
            </td>
        </tr>
    );
}
