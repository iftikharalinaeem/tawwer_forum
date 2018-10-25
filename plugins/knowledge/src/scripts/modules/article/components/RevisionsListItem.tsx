/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { IArticleRevisionFragment } from "@knowledge/@types/api";
import { Link } from "react-router-dom";
import DateTime from "@library/components/DateTime";
import classNames from "classnames";
import {
    revisionStatus_revision,
    revisionStatus_draft,
    revisionStatus_pending,
    revisionStatus_published,
    revisionStatus_deleted,
} from "@library/components/Icons";

export interface IArticleRevisionWithUrl extends IArticleRevisionFragment {
    url: string;
}

/**
 * Implements the Article Revision Item Component
 */
export default class RevisionsListItem extends React.Component<IArticleRevisionWithUrl> {
    public render() {
        const { name, status, dateInserted, url } = this.props;
        const { photoUrl } = this.props.insertUser;
        return (
            <li className="revisionsList-item">
                <Link to={url} className="revisionsList-link panelList-link" tabIndex={-1}>
                    <div className="revisionsList-photoFrame">
                        <img src={photoUrl} className="revisionsList-photo" alt={`${t("User: ")}${name}`} />
                    </div>
                    <div className="revisionsList-content">
                        <div className="revisionsList-userName">{name}</div>
                        <div className="revisionsList-dateTime">
                            <DateTime timestamp={dateInserted} className="metaStyle" />
                        </div>
                    </div>
                    <div className={classNames("revisionsList-status", `status-${status.toLowerCase()}`)}>
                        {this.icon(status)}
                    </div>
                </Link>
            </li>
        );
    }
    private icon(status: string) {
        switch (status) {
            case "draft":
                return revisionStatus_draft();
            case "pending":
                return revisionStatus_pending();
            case "published":
                return revisionStatus_published();
            case "deleted":
                return revisionStatus_deleted();
            default:
                return revisionStatus_revision();
        }
    }
}
