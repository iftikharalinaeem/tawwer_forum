/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import * as React from "react";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import Hoverable from "@library/dom/Hoverable";
import SmartLink from "@library/routing/links/SmartLink";
import classNames from "classnames";
import DateTime from "@library/content/DateTime";
import { metasClasses } from "@library/styles/metasStyles";

interface IProps extends IResponseArticleDraft {
    url: string;
}

/**
 * Implements the drafts list item component.
 */
export default class DraftsListItem extends React.Component<IProps> {
    public render() {
        const { dateInserted, insertUser, url } = this.props;
        const classesMetas = metasClasses();

        let name = t("(Unknown User)");
        let photoUrl: string | undefined;

        if (insertUser) {
            name = insertUser.name;
            photoUrl = insertUser.photoUrl;
        }

        return (
            <li className="itemList-item">
                <SmartLink to={url} className={classNames("itemList-link", "panelList-link")} tabIndex={-1} replace>
                    <div className="itemList-photoFrame">
                        <img src={photoUrl} className="itemList-photo" alt={`${t("User: ")}${name}`} />
                    </div>
                    <div className="itemList-content">
                        <div className="itemList-userName">{name}</div>
                        <div className="itemList-dateTime">
                            <DateTime timestamp={dateInserted} className={classesMetas.metaStyle} />
                        </div>
                    </div>
                </SmartLink>
            </li>
        );
    }
}
