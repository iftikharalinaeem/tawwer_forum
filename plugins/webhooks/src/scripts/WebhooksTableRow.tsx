/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { IWebhook } from "@webhooks/WebhookTypes";
import { ButtonTypes } from "@library/forms/ButtonTypes";
import { DeleteIcon, EditIcon, WarningIcon } from "@library/icons/common";
import React from "react";
import Button from "@library/forms/Button";
import { useHistory } from "react-router";

interface IProps {
    webhook: IWebhook;
    onEditClick?: () => void;
    onDeleteClick: () => void;
}
export function WebhooksTableRow(props: IProps) {
    const { webhook } = props;
    const history = useHistory();
    return (
        <tr>
            <td>
                <DashboardMediaItem title={webhook.name} info={webhook.url} />
            </td>
            <td>
                <DashboardMediaItem title={webhook.status} info="" />
            </td>
            <td>
                <DashboardTableOptions>
                    <Button
                        className="btn-icon"
                        onClick={() => {
                            history.push(`/webhook-settings/${webhook.webhookID}/edit`);
                        }}
                        baseClass={ButtonTypes.ICON_COMPACT}>
                        <EditIcon />
                    </Button>
                    )}
                    {
                    <Button 
                        className="btn-icon"
                        onClick={props.onDeleteClick}
                        baseClass={ButtonTypes.ICON_COMPACT}>
                        <DeleteIcon />
                    </Button>
                    }
                </DashboardTableOptions>
            </td>
        </tr>
    );
}
