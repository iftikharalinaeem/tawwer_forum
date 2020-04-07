/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { IDeliveryFragment } from "@webhooks/DeliveryTypes";
import React from "react";
import { RightChevronIcon } from "@library/icons/common";
import { useHistory } from "react-router";
import moment from "moment";
import {
    RevisionStatusPublishedIcon,
    RevisionStatusPendingIcon,
    RevisionStatusDraftIcon,
} from "@library/icons/revision";


interface IProps {
    delivery: IDeliveryFragment;
}

export function DeliveryTableRow(props: IProps) {
    const { delivery } = props;
    const history = useHistory();
    const durationToSeconds = function(duration: number) {
        let seconds = (duration / 1000);
        return seconds + "s";
    };

    return (
        <tr>
            <td>
                <DashboardMediaItem title={delivery.webhookDeliveryID} info="" />
            </td>
            <td>
                <DashboardMediaItem title={moment(new Date(delivery.dateInserted)).format("YYYY-MM-DD hh:mm")} info="" />
            </td>
            <td>
                <DashboardMediaItem title={durationToSeconds(delivery.requestDuration)} info="" />
            </td>
            <td>
                <DashboardMediaItem title={delivery.responseCode} info="" />
            </td>
        </tr>
    );
}
