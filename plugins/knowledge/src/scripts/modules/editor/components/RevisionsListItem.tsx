/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IRevisionFragment } from "@knowledge/@types/api";
import { t } from "@library/dom/appUtils";
import DateTime from "@library/content/DateTime";
import {
    revisionStatus_deleted,
    revisionStatus_draft,
    revisionStatus_pending,
    revisionStatus_published,
    revisionStatus_revision,
} from "@library/icons/revision";
import SmartLink from "@library/routing/links/SmartLink";
import Hoverable from "@library/utility/Hoverable";
import classNames from "classnames";
import * as React from "react";
import { metasClasses } from "@library/styles/metasStyles";

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
        const { name, status, dateInserted, url, isSelected } = this.props;
        const { photoUrl } = this.props.insertUser;
        const classesMetas = metasClasses();
        return (
            <Hoverable onHover={this.props.onHover} duration={50}>
                {provided => (
                    <li {...provided} className="revisionsList-item">
                        <SmartLink
                            to={url}
                            className={classNames("revisionsList-link", "panelList-link", { isSelected })}
                            tabIndex={-1}
                            replace
                        >
                            <div className="revisionsList-photoFrame">
                                <img src={photoUrl} className="revisionsList-photo" alt={`${t("User: ")}${name}`} />
                            </div>
                            <div className="revisionsList-content">
                                <div className="revisionsList-userName">{name}</div>
                                <div className="revisionsList-dateTime">
                                    <DateTime timestamp={dateInserted} className={classesMetas.metaStyle} />
                                </div>
                            </div>
                            {status && (
                                <div className={classNames("revisionsList-status", `status-${status.toLowerCase()}`)}>
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
        const commonClass = "revisionsList-icon";
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
