/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { IDeliveryFragment } from "@webhooks/WebhookTypes";
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
        var seconds = (duration / 1000);
        return seconds + "s";
    };

    return (
        <tr>
            <td>
                {/* <Link to={"/test"} className={"test"}> */}
                    <DashboardMediaItem title={delivery.webhookDeliveryID} />
                {/* </Link> */}
                {/* <Button baseClass={ButtonTypes.ICON} onClick={history}>
                    <RightChevronIcon centred={true} />
                </Button> */}
            </td>
            <td>
                <DashboardMediaItem title={moment(new Date(delivery.dateInserted)).format("YYYY-MM-DD hh:mm")} />
            </td>
            <td>
                <DashboardMediaItem title={durationToSeconds(delivery.requestDuration)} />
            </td>
            <td>
                <DashboardMediaItem title={delivery.responseCode} />
            </td>
        </tr>
    );
}
