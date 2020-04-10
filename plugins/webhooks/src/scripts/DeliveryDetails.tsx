/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import UserContent from "@vanilla/library/src/scripts/content/UserContent";
import { escapeHTML } from "@vanilla/dom-utils";
import { IDelivery } from "@webhooks/DeliveryTypes";
import React, { useEffect, useState } from "react";
import { t } from "@vanilla/i18n";
import Loader from "@library/loaders/Loader";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { deliveryDetailsCSSClasses } from "@webhooks/DeliveryDetailsStyles";
import { Tabs } from "@library/sectioning/Tabs";
import { useDeliveryData } from "@webhooks/DeliveryHooks";
import { deliveryTabsCSSClasses } from "@webhooks/DeliveryTabsStyles";
import { useDeliveryActions } from "./DeliveryActions";

interface IProps {
    webhookID?: number;
    webhookDeliveryID?: string;
}
export function DeliveryDetails(props: IProps) {
    const { webhookID, webhookDeliveryID } = props;
    const { deliveriesByDeliveryID } = useDeliveryData();
    const { getDeliveryByID } = useDeliveryActions();
    const deliveryDetailsClasses = deliveryDetailsCSSClasses();
    const [deliveryRecord, setDeliveryRecord] = useState<string | null>(null);
    let requestBody = "";
    let requestHeaders = "";
    let responseBody = "";
    let responseHeaders = "";
    let header = "";
    let body = "";

    useEffect(() => {
        if (webhookID && webhookDeliveryID) {
            getDeliveryByID(webhookID, webhookDeliveryID);
            setDeliveryRecord(deliveriesByDeliveryID.data);
            // alert(JSON.stringify(deliveryRecord));
        }
    }, [getDeliveryByID, webhookDeliveryID, webhookID]);

    const prettyPrintJSONString = function (paramJson) {
        let parsedString = "";
        if (paramJson != "") {
            parsedString = JSON.stringify(paramJson, null, 2);
        }
        return parsedString;
    };
    const prettyPrintHTTPHeaders = function (headers) {
        let joinedHeaders = "";
        headers = headers.split("\n");
        const prettyHeaders = headers.map((header) => {
            return header.replace(/[a-zA-Z-_]+:/g, "<strong>$&</strong>");
        });
        joinedHeaders = prettyHeaders.join("\n");
        return joinedHeaders;
    };

    if (deliveriesByDeliveryID.status === LoadStatus.LOADING) {
        return <Loader />;
    }

    if (deliveriesByDeliveryID.data) {
        requestBody = deliveriesByDeliveryID.data.requestBody;
        requestHeaders = deliveriesByDeliveryID.data.requestHeaders;
        responseBody = deliveriesByDeliveryID.data.responseBody;
        responseHeaders = deliveriesByDeliveryID.data.responseHeaders;
        header = "Header";
        body = "Body";
    }

    return (
        <div className={deliveryDetailsClasses.root}>
            <Tabs
                classes={deliveryTabsCSSClasses()}
                data={[
                    {
                        label: t("Request"),
                        panelData: "requestTab",
                        contents: (
                            <>
                                <div className="Request-headers">
                                    <h4 className={deliveryDetailsClasses.title}>{header}</h4>
                                    <UserContent
                                        content={`<pre class="code codeBlock">${prettyPrintHTTPHeaders(
                                            requestHeaders,
                                        )}</pre>`}
                                    />
                                </div>
                                <div className="Request-body">
                                    <h4 className={deliveryDetailsClasses.title}>{body}</h4>
                                    <UserContent
                                        content={`<pre class="code codeBlock">${prettyPrintJSONString(
                                            escapeHTML(requestBody),
                                        )}</pre>`}
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
                                    <h4 className={deliveryDetailsClasses.title}>{header}</h4>
                                    <UserContent
                                        content={`<pre class="code codeBlock">${prettyPrintHTTPHeaders(
                                            responseHeaders,
                                        )}</pre>`}
                                    />
                                </div>
                                <div className="Response-body">
                                    <h4 className={deliveryDetailsClasses.title}>{body}</h4>
                                    <UserContent
                                        content={`<pre class="code codeBlock">${prettyPrintJSONString(
                                            escapeHTML(responseBody),
                                        )}</pre>`}
                                    />
                                </div>
                            </>
                        ),
                    },
                ]}
            />
        </div>
    );
}
