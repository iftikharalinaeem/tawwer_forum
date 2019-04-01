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
        const { dateInserted, url } = this.props;
        const { name, photoUrl } = this.props.insertUser;
        const classesMetas = metasClasses();

        return (
            <li className="draftsList-item">
                <SmartLink to={url} className={classNames("draftsList-link", "panelList-link")} tabIndex={-1} replace>
                    <div className="draftsList-photoFrame">
                        <img src={photoUrl} className="draftsList-photo" alt={`${t("User: ")}${name}`} />
                    </div>
                    <div className="draftsList-content">
                        <div className="draftsList-userName">{name}</div>
                        <div className="draftsList-dateTime">
                            <DateTime timestamp={dateInserted} className={classesMetas.metaStyle} />
                        </div>
                    </div>
                </SmartLink>
            </li>
        );
    }
}
