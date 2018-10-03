/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DropDown from "@library/components/dropdown/DropDown";
import classNames from "classnames";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import DropDownItemMetas from "@library/components/dropdown/items/DropDownItemMetas";
import DropDownItemSeparator from "@library/components/dropdown/items/DropDownItemSeparator";
import { t } from "@library/application";
import { InlineTypes } from "@library/components/Sentence";
import { getRequiredID } from "@library/componentIDs";
import { ButtonBaseClass } from "@library/components/forms/Button";

export interface IProps {
    id: string;
    name?: string;
    className?: string;
}

export interface IState {
    id: string;
}

/**
 * Generates drop down menu for Article page
 */
export default class ArticleMenu extends React.PureComponent<IProps, IState> {
    public static defaultProps = {
        name: t("Article Options"),
    };

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "articleMenuDropDown"),
        };
    }

    public render() {
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
            <DropDown
                id={this.state.id}
                name={this.props.name!}
                className={classNames("articlePage-options", this.props.className)}
            >
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
