/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import * as React from "react";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import SmartLink from "@library/routing/links/SmartLink";
import classNames from "classnames";
import DateTime from "@library/content/DateTime";
import { metasClasses } from "@library/styles/metasStyles";
import { itemListClasses } from "@knowledge/modules/editor/components/itemListStyles";
import { panelListClasses } from "@library/layout/panelListStyles";
import { useLayout } from "@library/layout/LayoutContext";

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
        const classes = itemListClasses();
        const classesPanelList = panelListClasses(useLayout().mediaQueries);

        let name = "(" + t("Unknown User") + ")";
        let photoUrl: string | undefined;

        if (insertUser) {
            name = insertUser.name;
            photoUrl = insertUser.photoUrl;
        }

        return (
            <li className={classNames("itemList-item", classes.item)}>
                <SmartLink
                    to={url}
                    className={classNames("itemList-link", classes.link, "panelList-link", classesPanelList.link)}
                    tabIndex={-1}
                >
                    {photoUrl && photoUrl !== "" && (
                        <div className={classNames("itemList-photoFrame", classes.photoFrame)}>
                            <img
                                src={photoUrl}
                                className={classNames("itemList-photo", classes.photo)}
                                alt={`${t("User") + ": "}${name}`}
                            />
                        </div>
                    )}
                    <div className={classNames("itemList-content", classes.content)}>
                        <div className={classNames("itemList-userName", classes.userName)}>{name}</div>
                        <div className={classNames("itemList-dateTime", classes.dateTime)}>
                            <DateTime timestamp={dateInserted} className={classesMetas.metaStyle} />
                        </div>
                    </div>
                </SmartLink>
            </li>
        );
    }
}
