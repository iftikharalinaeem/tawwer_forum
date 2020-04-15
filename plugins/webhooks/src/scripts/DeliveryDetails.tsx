/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import UserContent from "@vanilla/library/src/scripts/content/UserContent";
import { escapeHTML } from "@vanilla/dom-utils";
import React, { useEffect, useState } from "react";
import { t } from "@vanilla/i18n";
import Loader from "@library/loaders/Loader";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { deliveryDetailsCSSClasses } from "@webhooks/DeliveryDetailsStyles";
import { Tabs } from "@library/sectioning/Tabs";
import { useDeliveryData } from "@webhooks/DeliveryHooks";
import { useDeliveryActions } from "./DeliveryActions";
import classNames from "classnames";
import { IDelivery } from "./DeliveryTypes";
import { TabsTypes } from "@vanilla/library/src/scripts/sectioning/TabsTypes";

interface IProps {
    webhookID: number;
    webhookDeliveryID: string;
    isActive: boolean;
}
export function DeliveryDetails(props: IProps) {
    const { webhookID, webhookDeliveryID, isActive } = props;
    const { deliveriesByDeliveryID } = useDeliveryData();
    const { getDeliveryByID } = useDeliveryActions();
    const deliveryDetailsClasses = deliveryDetailsCSSClasses();
    const [deliveryRecord, setDeliveryRecord] = useState<IDelivery | undefined>(undefined);

    useEffect(() => {
        if (
            isActive &&
            (!deliveriesByDeliveryID[webhookDeliveryID] ||
                deliveriesByDeliveryID[webhookDeliveryID].status !== LoadStatus.SUCCESS)
        ) {
            getDeliveryByID(webhookID, webhookDeliveryID);
        }
    }, [getDeliveryByID, webhookDeliveryID, webhookID, isActive]);

    useEffect(() => {
        if (deliveriesByDeliveryID[webhookDeliveryID] && deliveriesByDeliveryID[webhookDeliveryID].data) {
            setDeliveryRecord(deliveriesByDeliveryID[webhookDeliveryID].data);
        }
    }, [deliveriesByDeliveryID]);

    const isJson = function(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    };

    const prettyPrintJSONString = function(paramJson?: string): string {
        let parsedString = "";
        if (paramJson && paramJson.length !== 0) {
            parsedString = JSON.stringify(JSON.parse(paramJson), null, 2);
        }
        return parsedString;
    };
    const prettyPrintHTTPHeaders = function(headers?): string {
        let joinedHeaders = "";
        if (headers !== undefined) {
            let arrHeaders = headers.split("\n");
            arrHeaders = arrHeaders.map(header => {
                return header.replace(/[a-zA-Z-_]+:/g, "<strong>$&</strong>");
            });
            joinedHeaders = arrHeaders.join("\n");
        }
        return joinedHeaders;
    };

    if (
        deliveriesByDeliveryID[webhookDeliveryID] !== undefined &&
        isActive &&
        deliveriesByDeliveryID[webhookDeliveryID].status !== LoadStatus.SUCCESS
    ) {
        return <Loader />;
    }

    return (
        <div
            className={classNames("deliveryDetails", deliveryDetailsClasses.root, isActive ? "isActive" : "")}
            data-collapsed={!isActive}
        >
            {deliveryRecord !== undefined && (
                <Tabs
                    tabType={TabsTypes.BROWSE}
                    data={[
                        {
                            label: t("Request"),
                            panelData: "requestTab",
                            contents: (
                                <>
                                    <div className="Request-headers">
                                        <h4 className={deliveryDetailsClasses.title}>{t("Header")}</h4>
                                        <UserContent
                                            content={`<pre class="code codeBlock http">${prettyPrintHTTPHeaders(
                                                deliveryRecord.requestHeaders,
                                            )}</pre>`}
                                        />
                                    </div>
                                    <div className="Request-body">
                                        <h4 className={deliveryDetailsClasses.title}>{t("Body")}</h4>
                                        <UserContent
                                            content={`<pre class="code codeBlock">${
                                                isJson(deliveryRecord.requestBody)
                                                    ? prettyPrintJSONString(escapeHTML(deliveryRecord.requestBody))
                                                    : escapeHTML(deliveryRecord.requestBody)
                                            }</pre>`}
                                        />
                                    </div>
                                </>
                            ),
                        },
                        {
                            label: t("Response"),
                            panelData: "responseTab",
                            contents: (
                                <>
                                    <div className="Response-headers">
                                        <h4 className={deliveryDetailsClasses.title}>{t("Header")}</h4>
                                        <UserContent
                                            content={`<pre class="code codeBlock http">${prettyPrintHTTPHeaders(
                                                deliveryRecord.responseHeaders,
                                            )}</pre>`}
                                        />
                                    </div>
                                    <div className="Response-body">
                                        <h4 className={deliveryDetailsClasses.title}>{t("Body")}</h4>
                                        <UserContent
                                            content={`<pre class="code codeBlock">${
                                                isJson(deliveryRecord.responseBody)
                                                    ? prettyPrintJSONString(escapeHTML(deliveryRecord.responseBody))
                                                    : escapeHTML(deliveryRecord.responseBody)
                                            }</pre>`}
                                        />
                                    </div>
                                </>
                            ),
                        },
                    ]}
                />
            )}
        </div>
    );
}
