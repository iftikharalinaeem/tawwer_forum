/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import DropDown from "@library/flyouts/DropDown";
import { t } from "@library/dom/appUtils";
import { InlineTypes } from "@library/content/Sentence";

import { DropDownItemButton, DropDownItemMetas, DropDownItemSeparator } from "@library/flyouts";

export interface IProps {}

/**
 * Generates drop down menu for Article page
 */
export default class ArticleMenu extends React.PureComponent<IProps> {
    public render() {
        const domID = "categoryMenuDropDown"; // Todo: dynamic
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

        return (
            <DropDown id={domID} name={t("Category Options")} className={"categoryPage-options"}>
                <DropDownItemMetas>{publishedMeta}</DropDownItemMetas>
                <DropDownItemMetas>{updatedMeta}</DropDownItemMetas>
                <DropDownItemSeparator />
                <DropDownItemButton name={t("Customize SEO")} onClick={buttonClick} />
                <DropDownItemButton name={t("Move")} onClick={buttonClick} />
                <DropDownItemSeparator />
                <DropDownItemButton name={t("Revision History")} onClick={buttonClick} />
                <DropDownItemSeparator />
                <DropDownItemButton name={t("Delete")} onClick={buttonClick} />
            </DropDown>
        );
    }
}
