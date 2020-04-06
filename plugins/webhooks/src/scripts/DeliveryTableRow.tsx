/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { IDelivery } from "@webhooks/WebhookTypes";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DeleteIcon, EditIcon } from "@library/icons/common";
import React from "react";
import Button from "@library/forms/Button";
import { useHistory } from "react-router";
import {
    RevisionStatusPublishedIcon,
    RevisionStatusPendingIcon,
    RevisionStatusDraftIcon,
} from "@library/icons/revision";
interface IProps {
    delivery: IDelivery;
    onClick?: () => void;
}
export function DeliveryTableRow(props: IProps) {
    const { delivery } = props;
    const history = useHistory();
    return (
        <tr>
            <td>
                <DashboardMediaItem title={delivery.webhookDeliveryID} />
            </td>
            <td>
                <DashboardMediaItem title={delivery.dateInserted} />
            </td>

            <td>
                <DashboardMediaItem title={delivery.requestDuration} />
            </td>
            <td>
                <DashboardMediaItem title={delivery.responseCode} />
            </td>
        </tr>
    );
}
