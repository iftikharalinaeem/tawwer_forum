/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { IDelivery, IDeliveryFragment } from "@webhooks/DeliveryTypes";
import React from "react";
import moment from "moment";
import { Link } from "react-router-dom";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { RightChevronIcon } from "@library/icons/common";
import { DeliveryDetails } from "@webhooks/DeliveryDetails";

interface IProps {
    delivery: IDeliveryFragment;
    onClick?: () => void;
    buttonClicked?: false;
}

export function DeliveryTableRow(props: IProps) {
    const { delivery, buttonClicked } = props;

    const durationToSeconds = function (duration: number) {
        let seconds = duration / 1000;
        return seconds + "s";
    };

    return (
        <td colSpan={4}>
            <div>
                <div>
                    <Link to={"/test"} className={"test"}>
                        <DashboardMediaItem title={delivery.webhookDeliveryID} info="" />
                    </Link>
                    <Button baseClass={ButtonTypes.ICON} onClick={props.onClick}>
                        <RightChevronIcon centred={true} />
                    </Button>
                </div>
                <div>
                    <DashboardMediaItem
                        title={moment(new Date(delivery.dateInserted)).format("YYYY-MM-DD hh:mm")}
                        info=""
                    />
                </div>
                <div>
                    <DashboardMediaItem title={durationToSeconds(delivery.requestDuration)} info="" />
                </div>
                <div>
                    <DashboardMediaItem title={String(delivery.responseCode)} info="" />
                </div>
            </div>
            {buttonClicked && (
                <DeliveryDetails webhookDeliveryID={delivery.webhookDeliveryID} webhookID={delivery.webhookID} />
            )}
        </td>
    );
}
