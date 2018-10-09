/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import classNames from "classnames";
import DropDown from "@library/components/dropdown/DropDown";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";

export interface ISelectboxItem {
    name: string;
    className?: string;
    onClick: () => {};
    selected: boolean;
}

interface IProps {
    label: string;
    className?: string;
    id?: string;
    children: ISelectboxItem[];
    selectedIndex: number;
}

interface IState {
    id: string;
    selectedIndex: number;
}

export default class Sort extends React.Component<IProps, IState> {
    public static defaultProps = {
        selectedIndex: 0,
    };

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "selectBox-"),
            selectedIndex: this.props.selectedIndex,
        };
    }


    public render() {
        const selectItems = this.props.children.map((child, i) => {
            return (
                <DropDownItemButton key={this.props.id + "-item" + i} className="selectBox-option" name={t("Customize SEO")} onClick={this.onClick} disabled={i === this.state.selectedIndex}>
                    {child.name}
                </DropDownItemButton>
            );
        });

        return (
            <div className={classNames("selectBox", this.props.className)}>
                <span className="selectBox-label">
                    {this.props.label}
                </span>
                <div className="selectBox-content">
                    <DropDown id={this.state.id} name={this.props.label}>
                        {selectItems}
                    </DropDown>
                </div>
            </div>
        );
    }

    private onClick = (index: number) => {
        this.setState({
            selectedIndex: index,
        });
    }
}
