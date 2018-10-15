/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import DropDown from "@library/components/dropdown/DropDown";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import { checkCompact, rightChevron } from "@library/components/Icons";
import { ButtonBaseClass } from "@library/components/forms/Button";

export interface ISelectBoxItem {
    name: string;
    className?: string;
    onClick: () => {};
    selected?: boolean;
    outdated?: boolean;
}

interface IProps {
    className?: string;
    id?: string;
    children: ISelectBoxItem[];
}

export interface ISelfLabelledProps extends IProps {
    label: string;
}

export interface IExternalLabelledProps extends IProps {
    describedBy: string;
}

interface IState {
    id: string;
    selectedIndex: number;
    selectedItem: any;
}

/**
 * Generates Select Box component (similar to a select)
 */
export default class Sort extends React.Component<ISelfLabelledProps | IExternalLabelledProps, IState> {
    public static defaultProps = {
        selectedIndex: 0,
    };

    public constructor(props) {
        super(props);

        this.state = {
            id: getRequiredID(props, "selectBox-"),
            selectedIndex: props.selectedIndex,
            selectedItem: props.selectedItem || props.children[props.selectedIndex],
        };
    }

    public handleClick = (selectedItem: ISelectBoxItem, index: number) => {
        this.setState({
            selectedIndex: index,
            selectedItem,
        });
    };

    public render() {
        const selectItems =
            this.props.children && this.props.children.length > 0
                ? this.props.children.map((child, i) => {
                      return (
                          <DropDownItemButton
                              key={this.props.id + "-item" + i}
                              className={classNames("selectBox-option", { isSelected: child.selected })}
                              name={child.name}
                              onClick={this.handleClick.bind(this, child, i)}
                              disabled={i === this.state.selectedIndex}
                              clickData={child}
                              index={i}
                              current={child.selected}
                              buttonClassName="selectBox-buttonItem"
                          >
                              <span className="selectBox-checkContainer sc-only">
                                  {child.selected && checkCompact()}
                              </span>
                              {child.name}
                              {child.outdated && t("(Outdated)")}
                          </DropDownItemButton>
                      );
                  })
                : null;
        const buttonContents =
            this.state.selectedItem && this.state.selectedItem.name ? (
                <React.Fragment>
                    {this.state.selectedItem.name}
                    {rightChevron()}
                </React.Fragment>
            ) : null;
        return (
            <div
                aria-describedby={"describedBy" in this.props ? this.props.describedBy : undefined}
                className={classNames("selectBox", this.props.className)}
            >
                {"label" in this.props && <span className="selectBox-label">{this.props.label}</span>}
                <div className="selectBox-content">
                    <DropDown
                        id={this.state.id}
                        className="selectBox-dropDown"
                        name={"label" in this.props ? this.props.label : this.state.selectedItem.name}
                        buttonContents={buttonContents}
                        buttonClassName="selectBox-buttonItem"
                        buttonBaseClass={ButtonBaseClass.TEXT}
                    >
                        {selectItems}
                    </DropDown>
                </div>
            </div>
        );
    }
}
