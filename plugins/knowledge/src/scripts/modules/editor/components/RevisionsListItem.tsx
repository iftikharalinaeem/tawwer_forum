/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import DateTime from "@library/content/DateTime";
import {
    revisionStatus_deleted,
    revisionStatus_draft,
    revisionStatus_pending,
    revisionStatus_published,
    revisionStatus_revision,
} from "@library/icons/revision";
import SmartLink from "@library/routing/links/SmartLink";
import Hoverable from "@library/dom/Hoverable";
import classNames from "classnames";
import * as React from "react";
import { metasClasses } from "@library/styles/metasStyles";
import { IRevisionFragment } from "@knowledge/@types/api/articleRevision";

interface IProps extends IRevisionFragment {
    url: string;
    isSelected: boolean;
    onHover?: () => void;
}

/**
 * Implements the Article Revision Item Component
 */
export default class RevisionsListItem extends React.Component<IProps> {
    public render() {
        const { status, dateInserted, url, isSelected } = this.props;
        const { name, photoUrl } = this.props.insertUser;
        const classesMetas = metasClasses();
        return (
            <Hoverable onHover={this.props.onHover} duration={250}>
                {provided => (
                    <li {...provided} className="itemList-item">
                        <SmartLink
                            to={url}
                            className={classNames("itemList-link", "panelList-link", { isSelected })}
                            tabIndex={-1}
                            replace
                        >
                            <div className="itemList-photoFrame">
                                <img src={photoUrl} className="itemList-photo" alt={`${t("User")+': '}${name}`} />
                            </div>
                            <div className="itemList-content">
                                <div className="itemList-userName">{name}</div>
                                <div className="itemList-dateTime">
                                    <DateTime timestamp={dateInserted} className={classesMetas.metaStyle} />
                                </div>
                            </div>
                            {status && (
                                <div className={classNames("itemList-status", `status-${status.toLowerCase()}`)}>
                                    {this.icon(status)}
                                </div>
                            )}
                        </SmartLink>
                    </li>
                )}
            </Hoverable>
        );
    }

    private icon(status: string) {
        const commonClass = "itemList-icon";
        switch (status) {
            case "draft":
                return revisionStatus_draft(commonClass);
            case "pending":
                return revisionStatus_pending(commonClass);
            case "published":
                return revisionStatus_published(commonClass);
            case "deleted":
                return revisionStatus_deleted(commonClass);
            default:
                return revisionStatus_revision(commonClass);
        }
    }
}
