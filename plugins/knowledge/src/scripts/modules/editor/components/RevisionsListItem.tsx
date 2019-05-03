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
import { itemListClasses } from "@knowledge/modules/editor/components/itemListStyles";

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
        const classes = itemListClasses();
        return (
            <Hoverable onHover={this.props.onHover} duration={250}>
                {provided => (
                    <li {...provided} className={classNames(classes.item, "itemList-item")}>
                        <SmartLink
                            to={url}
                            className={classNames("itemList-link", classes.link, "panelList-link", { isSelected })}
                            tabIndex={-1}
                            replace
                        >
                            <div className={classNames("itemList-photoFrame", classes.photoFrame)}>
                                <img
                                    src={photoUrl}
                                    className={classNames("itemList-photo", classes.photo)}
                                    alt={`${t("User") + ": "}${name}`}
                                />
                            </div>
                            <div className={classNames("itemList-content", classes.content)}>
                                <div className={classNames("itemList-userName", classes.userName)}>{name}</div>
                                <div className={classNames("itemList-dateTime", classes.dateTime)}>
                                    <DateTime timestamp={dateInserted} className={classesMetas.metaStyle} />
                                </div>
                            </div>
                            {status && (
                                <div
                                    className={classNames(
                                        "itemList-status",
                                        classes.status,
                                        `status-${status.toLowerCase()}`,
                                    )}
                                >
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
        const classes = itemListClasses();
        const commonClass = classNames("itemList-icon", classes.icon);
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
