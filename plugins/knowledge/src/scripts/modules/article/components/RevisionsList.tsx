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

interface IProps {
    children: IArticleRevisionWithUrl[];
}

/*
    articleRevisionID: 1,
    articleID: 1,
    status: "published",
    name: "Some Name",
    format: Format.RICH,
    bodyRendered: "Hello",
    locale: "en",
    insertUser: {
        userID: 1,
        name: "Joe",
        photoUrl: "#",
        dateLastActive: "2019-10-18",
    },
    dateInserted: "2019-10-19",
    url: revisionUrl,
 */

/**
 * Implements the Article Revision History component
 */
export default class RevisionsList extends React.Component<IProps> {
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

    public render() {
        const history = this.props.children.map(child => {
            const { name, photoUrl } = child.insertUser;

            return (
                <li className="revisionsList-item">
                    <Link to={child.url} className="revisionsList-link panelList-link" tabIndex={-1}>
                        <div className="revisionsList-photoFrame">
                            <img src={photoUrl} className="revisionsList-photo" alt={`${t("User: ")}${name}`} />
                        </div>
                        <div className="revisionsList-content">
                            <div className="revisionsList-userName">{name}</div>
                            <div className="revisionsList-dateTime">
                                <DateTime timestamp={child.dateInserted} className="metaStyle" />
                            </div>
                        </div>
                        <div className={classNames("revisionsList-status", `status-${child.status.toLowerCase()}`)}>
                            {this.icon(child.status)}
                        </div>
                    </Link>
                </li>
            );
        });

        return (
            <div className="revisionsList related">
                <Heading
                    className="panelList-title revisionsList-title"
                    title={t("Revisions")}
                    depth={1}
                    renderAsDepth={2}
                />
                <ul className="revisionsList-items panelList-items">{history}</ul>
            </div>
        );
    }
}
