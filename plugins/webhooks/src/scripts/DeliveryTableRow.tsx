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
import { DeliveryDetailsCSSClasses } from "./DeliveryDetailsStyles";

interface IProps {
    delivery: IDeliveryFragment;
    onClick?: () => void;
}

export function DeliveryTableRow(props: IProps) {
    const { delivery } = props;
    const DeliveryDetailsClasses = DeliveryDetailsCSSClasses();

    const durationToSeconds = function (duration: number) {
        let seconds = duration / 1000;
        return seconds + "s";
    };
    const prettyPrintJSONString = function (jsonString) {
        return JSON.stringify(JSON.parse(jsonString), null, 2);
    };
    const prettyPrintHTTPHeaders = function (headers) {
        headers = headers.split("\n");
        const prettyHeaders = headers.map((header) => {
            return header.replace(/[a-zA-Z-_]+:/g, "<strong>$&</strong>");
        });
        return prettyHeaders.join("\n");
    };

    //mock data
    const requestBody =
        '{"action":"insert","payload":{"discussion":{"discussionID":22,"type":null,"name":"Deliveries trigger!","body":"Deliveries trigger","categoryID":7,"dateInserted":{"date":"2020-04-06 19:05:04.000000","timezone_type":3,"timezone":"UTC"},"dateUpdated":null,"dateLastComment":{"date":"2020-04-06 19:05:04.000000","timezone_type":3,"timezone":"UTC"},"insertUserID":2,"insertUser":{"userID":2,"name":"Tester_McTesterson","photoUrl":"https://dev.vanilla.localhost/uploads/userpics/357/n97D458G4MZJ8.gif","dateLastActive":{"date":"2020-04-06 19:04:13.000000","timezone_type":3,"timezone":"UTC"},"label":"admin"},"lastUser":{"userID":2,"name":"Tester_McTesterson","photoUrl":"https://dev.vanilla.localhost/uploads/userpics/357/n97D458G4MZJ8.gif","dateLastActive":{"date":"2020-04-06 19:04:13.000000","timezone_type":3,"timezone":"UTC"},"label":"admin"},"pinned":false,"pinLocation":null,"closed":false,"sink":false,"countComments":0,"countViews":1,"score":null,"url":"https://dev.vanilla.localhost/discussion/22/deliveries-trigger","canonicalUrl":"https://dev.vanilla.localhost/discussion/22/deliveries-trigger","lastPost":{"discussionID":22,"name":"Deliveries trigger!","url":"https://dev.vanilla.localhost/discussion/22/deliveries-trigger","dateInserted":{"date":"2020-04-06 19:05:04.000000","timezone_type":3,"timezone":"UTC"},"insertUserID":2,"insertUser":{"userID":2,"name":"Tester_McTesterson","photoUrl":"https://dev.vanilla.localhost/uploads/userpics/357/n97D458G4MZJ8.gif","dateLastActive":{"date":"2020-04-06 19:04:13.000000","timezone_type":3,"timezone":"UTC"},"label":"admin"}}}},"sender":{"userID":2,"name":"Tester_McTesterson","photoUrl":"https://dev.vanilla.localhost/uploads/userpics/357/n97D458G4MZJ8.gif","dateLastActive":{"date":"2020-04-06 19:04:13.000000","timezone_type":3,"timezone":"UTC"},"label":"admin"},"site":{"siteID":0}}';
    const requestReader =
        'Server: nginx\nDate: Mon, 06 Apr 2020 19:05:06 GMT\nContent-Type: application/json; charset=utf-8\nContent-Length: 16\nConnection: keep-alive\nCache-Control: no-cache, no-store, must-revalidate\nPragma: no-cache\nExpires: Fri, 31 Dec 1998 12:00:00 GMT\nX-RateLimit-Limit: 300\nX-RateLimit-Reset: 899\nX-RateLimit-Remaining: 299\nETag: W/"10-oV4hJxRVSENxc/wX8+mA4/Pe4tA"\nAccess-Control-Allow-Origin: *\nAccess-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE\nAccess-Control-Allow-Headers: DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range\nAccess-Control-Expose-Headers: Content-Length,Content-Range';

    return (
        <>
            <tr>
                <td>
                    <Link to={"/test"} className={"test"}>
                        <DashboardMediaItem title={delivery.webhookDeliveryID} info="" />
                    </Link>
                    <Button baseClass={ButtonTypes.ICON} onClick={props.onClick}>
                        <RightChevronIcon centred={true} />
                    </Button>
                </td>
                <td>
                    <DashboardMediaItem
                        title={moment(new Date(delivery.dateInserted)).format("YYYY-MM-DD hh:mm")}
                        info=""
                    />
                </td>
                <td>
                    <DashboardMediaItem title={durationToSeconds(delivery.requestDuration)} info="" />
                </td>
                <td>
                    <DashboardMediaItem title={String(delivery.responseCode)} info="" />
                </td>
            </tr>
            <tr>
                <td className={DeliveryDetailsClasses.root} colSpan={4}>
                    <div className="Response-headers">
                        <h4 className={DeliveryDetailsClasses.title}>{t("Headers")}</h4>
                        <UserContent
                            content={`<pre class="code codeBlock">${prettyPrintHTTPHeaders(requestReader)}</pre>`}
                        />
                    </div>
                    <div className="Response-body">
                        <h4 className={DeliveryDetailsClasses.title}>{t("Boby")}</h4>
                        <UserContent
                            content={`<pre class="code codeBlock">${prettyPrintJSONString(
                                escapeHTML(requestBody),
                            )}</pre>`}
                        />
                    </div>
                </td>
            </tr>
        </>
    );
}
