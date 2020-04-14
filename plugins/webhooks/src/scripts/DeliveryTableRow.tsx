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
import classNames from "classnames";

interface IProps {
    delivery: IDeliveryFragment;
    onClick?: (e) => void;
    isActive: boolean;
    index: number;
}

export function DeliveryTableRow(props: IProps) {
    let { delivery, isActive, index } = props;
    const DeliveryTableRowClasses = deliveryTableRowCSSClasses();
    const durationToSeconds = function(duration: number) {
        let seconds = duration / 1000;
        return seconds + "s";
    };

    return (
        <td colSpan={4} className={classNames("DeliveryTableRow", DeliveryTableRowClasses.root)}>
            <div className={DeliveryTableRowClasses.rowDelivery}>
                <div className={DeliveryTableRowClasses.colDeliveryID}>
                    <Button
                        baseClass={ButtonTypes.ICON}
                        className="collapseDeliveryButton"
                        onClick={props.onClick}
                        data-index={index}
                    >
                        <span className="collapseIcon">
                            <RightChevronIcon centred={true} />
                        </span>
                        <span className={classNames("collapseLabel", DeliveryTableRowClasses.collapseLabel)}>
                            {delivery.webhookDeliveryID}
                        </span>
                    </Button>
                </div>
                <div className={TableColumnSize.XS}>
                    {moment(new Date(delivery.dateInserted)).format("YYYY-MM-DD hh:mm")}
                </div>
                <div className={TableColumnSize.XS}>{durationToSeconds(delivery.requestDuration)}</div>
                <div className={TableColumnSize.XS}>{String(delivery.responseCode)}</div>
            </div>
            <DeliveryDetails
                webhookDeliveryID={delivery.webhookDeliveryID}
                webhookID={delivery.webhookID}
                isActive={isActive}
            />
        </td>
    );
}
