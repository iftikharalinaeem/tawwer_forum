/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import {IWebhook, WebhookStatus} from "@webhooks/WebhookTypes";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DeleteIcon, EditIcon, WarningIcon } from "@library/icons/common";
import React from "react";
import Button from "@library/forms/Button";

interface IProps {
    webhook: IWebhook;
    forStatus: WebhookStatus;
    onEditClick?: () => void;
    onStatusChangeClick: () => void;
}
export function WebhooksTableRow(props: IProps) {
    const webhook = props.webhook;

    return (
        <tr>
            <td>
                <DashboardMediaItem title={webhook.name} info={webhook.url}/>
            </td>
            <td>
                <DashboardMediaItem title={webhook.status} info= ''/>
            </td>
            <td>
                <DashboardTableOptions>
                    {props.onEditClick && (
                        <Button className="btn-icon" onClick={props.onEditClick} baseClass={ButtonTypes.ICON_COMPACT}>
                            <EditIcon />
                        </Button>
                    )}
                    {props.onStatusChangeClick && (
                        <Button className="btn-icon" onClick={props.onStatusChangeClick} baseClass={ButtonTypes.ICON_COMPACT}>
                            <DeleteIcon />
                        </Button>
                    )}
                </DashboardTableOptions>
            </td>
        </tr>
    );
}
