/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import { InlineTypes } from "@library/components/Sentence";
import { IArticle } from "@knowledge/@types/api";
import {
    DropDownItemLink,
    DropDownItemButton,
    DropDownItemMetas,
    DropDownItemSeparator,
} from "@library/components/dropdown";
import { makeEditUrl } from "@knowledge/modules/editor/route";
import classNames from "classnames";

export interface IProps {
    article: IArticle;
    buttonClassName?: string;
}

/**
 * Generates drop down menu for Article page
 */
export default class ArticleMenu extends React.PureComponent<IProps> {
    public render() {
        const { article } = this.props;
        const domID = "articleMenuDropDown-" + article.articleID;
        // Hard coded data/functions
        const buttonClick = () => {
            alert("Click works");
        };

        const publishedMeta = [
            {
                type: InlineTypes.TEXT,
                children: "Published ",
            },
            {
                type: InlineTypes.DATETIME,
                timeStamp: "2017-05-20 10:00",
                children: "20th May, 2018 10:00 AM",
            },
            {
                type: InlineTypes.TEXT,
                children: t(" by "),
            },
            {
                type: InlineTypes.LINK,
                to: "#user/Todd_Burry",
                children: "Todd Burry",
            },
        ];

        const updatedMeta = [
            {
                type: InlineTypes.TEXT,
                children: "Updated ",
            },
            {
                type: InlineTypes.DATETIME,
                timeStamp: "2017-05-20 10:00",
                children: "20th May, 2018 10:00 AM",
            },
            {
                type: InlineTypes.TEXT,
                children: t(" by "),
            },
            {
                type: InlineTypes.LINK,
                to: "#user/Todd_Burry",
                children: "Todd Burry",
            },
        ];

        const editUrl = makeEditUrl(article.articleID);

        return (
            <DropDown
                id={domID}
                name={t("Article Options")}
                buttonClassName={classNames("dropDown-toggleButtonIcon", this.props.buttonClassName)}
            >
                <DropDownItemMetas>{publishedMeta}</DropDownItemMetas>
                <DropDownItemMetas>{updatedMeta}</DropDownItemMetas>
                <DropDownItemSeparator />
                <DropDownItemButton name={t("Customize SEO")} onClick={buttonClick} />
                <DropDownItemButton name={t("Move")} onClick={buttonClick} />
                <DropDownItemLink name={t("Edit article")} to={editUrl} isModalLink={true} />
                <DropDownItemSeparator />
                <DropDownItemButton name={t("Revision History")} onClick={buttonClick} />
                <DropDownItemSeparator />
                <DropDownItemButton name={t("Delete")} onClick={buttonClick} />
            </DropDown>
        );
    }
}
