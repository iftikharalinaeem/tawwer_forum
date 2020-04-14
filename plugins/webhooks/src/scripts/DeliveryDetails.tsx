/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import UserContent from "@vanilla/library/src/scripts/content/UserContent";
import { escapeHTML } from "@vanilla/dom-utils";
import React, { useEffect } from "react";
import { t } from "@vanilla/i18n";
import Loader from "@library/loaders/Loader";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { deliveryDetailsCSSClasses } from "@webhooks/DeliveryDetailsStyles";
import { Tabs } from "@library/sectioning/Tabs";
import { useDeliveryData } from "@webhooks/DeliveryHooks";
import { deliveryTabsCSSClasses } from "@webhooks/DeliveryTabsStyles";
import { useDeliveryActions } from "./DeliveryActions";
import classNames from "classnames";

interface IProps {
    webhookID?: number;
    webhookDeliveryID?: string;
    isActive: boolean;
}
export function DeliveryDetails(props: IProps) {
    const { webhookID, webhookDeliveryID, isActive } = props;
    const { deliveriesByDeliveryID } = useDeliveryData();
    const { getDeliveryByID } = useDeliveryActions();
    const deliveryDetailsClasses = deliveryDetailsCSSClasses();
    let requestBody;
    let requestHeaders;
    let responseBody;
    let responseHeaders;

    useEffect(() => {
        if (webhookID && webhookDeliveryID && isActive) {
            getDeliveryByID(webhookID, webhookDeliveryID);
        }
    }, [getDeliveryByID, webhookDeliveryID, webhookID, isActive]);

    const isJson = function(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    };

    const prettyPrintJSONString = function(paramJson) {
        let parsedString = "";
        if (paramJson.length !== 0) {
            parsedString = JSON.stringify(JSON.parse(paramJson), null, 2);
        }
        return parsedString;
    };
    const prettyPrintHTTPHeaders = function(headers) {
        let joinedHeaders = "";
        if (typeof headers !== "undefined") {
            headers = headers.split("\n");
            const prettyHeaders = headers.map(header => {
                return header.replace(/[a-zA-Z-_]+:/g, "<strong>$&</strong>");
            });
            joinedHeaders = prettyHeaders.join("\n");
        }
        return joinedHeaders;
    };

    if (deliveriesByDeliveryID.status === LoadStatus.LOADING && isActive) {
        return <Loader />;
    }

    if (deliveriesByDeliveryID.data) {
        requestBody = deliveriesByDeliveryID.data.requestBody;
        requestHeaders = deliveriesByDeliveryID.data.requestHeaders;
        responseBody = deliveriesByDeliveryID.data.responseBody;
        responseHeaders = deliveriesByDeliveryID.data.responseHeaders;
    }

    return (
        <div
            className={classNames("deliveryDetails", deliveryDetailsClasses.root, isActive ? "isActive" : "")}
            data-collapsed={!isActive}
        >
            <Tabs
                classes={deliveryTabsCSSClasses()}
                data={[
                    {
                        label: t("Request"),
                        panelData: "requestTab",
                        contents: (
                            <>
                                <div className="Request-headers">
                                    <h4 className={deliveryDetailsClasses.title}>{t("Header")}</h4>
                                    <UserContent
                                        content={`<pre class="code codeBlock">${prettyPrintHTTPHeaders(
                                            requestHeaders,
                                        )}</pre>`}
                                    />
                                </div>
                                <div className="Request-body">
                                    <h4 className={deliveryDetailsClasses.title}>{t("Body")}</h4>
                                    <UserContent
                                        content={`<pre class="code codeBlock">${
                                            isJson(requestBody)
                                                ? prettyPrintJSONString(escapeHTML(requestBody))
                                                : escapeHTML(requestBody)
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
                                        content={`<pre class="code codeBlock">${prettyPrintHTTPHeaders(
                                            responseHeaders,
                                        )}</pre>`}
                                    />
                                </div>
                                <div className="Response-body">
                                    <h4 className={deliveryDetailsClasses.title}>{t("Body")}</h4>
                                    <UserContent
                                        content={`<pre class="code codeBlock">${
                                            isJson(responseBody)
                                                ? prettyPrintJSONString(escapeHTML(responseBody))
                                                : escapeHTML(responseBody)
                                        }</pre>`}
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
