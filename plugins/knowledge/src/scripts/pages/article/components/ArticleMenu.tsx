/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DropDown from "@library/components/dropdown/DropDown";
import classNames from "classnames";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import DropDownItemLink from "@library/components/dropdown/items/DropDownItemLink";
import DropDownItemMeta from "@library/components/dropdown/items/DropDownItemMeta";
import DropDownItemRadio from "@library/components/dropdown/items/DropDownItemRadio";
import DropDownItemSeparator from "@library/components/dropdown/items/DropDownItemSeparator";
import { t } from "@library/application";

export interface IProps {
    id: string;
    name?: string;
    className?: string;
}

export interface IState {
    id: string;
}

export default class DropDownActions extends React.PureComponent<IProps, IState> {
    public static defaultProps = {
        name: t("Article Options"),
    };

    public render() {
        // Hard coded for now
        const buttonClick = () => {
            alert("test");
        };

        return (
            <DropDown
                id={this.props.id}
                name={this.props.name!}
                className={classNames("articlePage-options", this.props.className)}
            >
                <DropDownItemButton name={t("Test Button")} onClick={buttonClick} />
                <DropDownItemButton name={t("Test Button")} onClick={buttonClick}>
                    <p>{t("Some HTML - Button")}</p>
                </DropDownItemButton>
                <DropDownItemLink url="/kb/" name={t("Test Link")} />
                <DropDownItemLink url="/kb/" name={t("Test Link")}>
                    <p>{t("Some HTML - Link")}</p>
                </DropDownItemLink>
                <DropDownItemMeta>
                    <React.Fragment />
                </DropDownItemMeta>
                <DropDownItemRadio />
                <DropDownItemSeparator />
            </DropDown>
        );
    }
}
