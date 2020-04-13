/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDeliveryFragment } from "@webhooks/DeliveryTypes";
import React from "react";
import moment from "moment";
import { Link } from "react-router-dom";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { RightChevronIcon } from "@library/icons/common";
import { DeliveryDetails } from "@webhooks/DeliveryDetails";
import { deliveryTableRowCSSClasses } from "@webhooks/DeliveryTableRowStyles";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";

interface IProps {
    delivery: IDeliveryFragment;
    onClick?: () => void;
    isActive: false;
}

export function DeliveryTableRow(props: IProps) {
    let { delivery, isActive } = props;
    const DeliveryTableRowClasses = deliveryTableRowCSSClasses();
    const durationToSeconds = function (duration: number) {
        let seconds = duration / 1000;
        return seconds + "s";
    };

    return (
        <td colSpan={4} className={DeliveryTableRowClasses.root}>
            <div className={DeliveryTableRowClasses.rowDelivery}>
                {console.log("isActive")}
                {console.log(isActive)}
                <div className={DeliveryTableRowClasses.colDeliveryID}>
                    <Button baseClass={ButtonTypes.ICON} className="collapseDeliveryButton" onClick={props.onClick}>
                        <RightChevronIcon centred={true} />
                    </Button>
                    <Link to={"/test"} className={"test"}>
                        {delivery.webhookDeliveryID}
                    </Link>
                </div>
                <div className={TableColumnSize.XS}>
                    {moment(new Date(delivery.dateInserted)).format("YYYY-MM-DD hh:mm")}
                </div>
                <div className={TableColumnSize.XS}>{durationToSeconds(delivery.requestDuration)}</div>
                <div className={TableColumnSize.XS}>{String(delivery.responseCode)}</div>
            </div>

            {isActive && (
                <DeliveryDetails webhookDeliveryID={delivery.webhookDeliveryID} webhookID={delivery.webhookID} />
            )}
        </td>
    );
}
