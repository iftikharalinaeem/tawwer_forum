/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDeliveryFragment } from "@webhooks/DeliveryTypes";
import React, { useState } from "react";
import moment from "moment";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { RightChevronIcon } from "@library/icons/common";
import { DeliveryDetails } from "@webhooks/DeliveryDetails";
import { DeliveryAccordionCSSClasses } from "@webhooks/DeliveryAccordionStyles";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import classNames from "classnames";

interface IProps {
    delivery: IDeliveryFragment;
    index: number;
}

export function DeliveryAccordion(props: IProps) {
    let { delivery, index } = props;
    const DeliveryTableRowClasses = DeliveryAccordionCSSClasses();
    const [activeAccordion, setActiveAccordion] = useState<number>(-1);

    const durationToSeconds = function(duration: number) {
        let seconds = duration / 1000;
        return seconds + "s";
    };

    return (
        <>
            <div
                className={classNames(
                    "DeliveryAccordion",
                    DeliveryTableRowClasses.root,
                    activeAccordion === index ? "isActive" : "",
                )}
                data-index={index}
            >
                <div className={DeliveryTableRowClasses.colDeliveryID}>
                    <Button
                        baseClass={ButtonTypes.ICON}
                        className="collapseDeliveryButton"
                        data-index={index}
                        onClick={() => {
                            setActiveAccordion(activeAccordion !== index ? index : -1);
                        }}
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
                isActive={activeAccordion === index}
            />
        </>
    );
}
