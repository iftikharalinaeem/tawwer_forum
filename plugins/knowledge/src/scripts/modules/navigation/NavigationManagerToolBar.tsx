/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";
import { expandAll, collapseAll } from "@library/components/icons/navigationManager";
import { newFolder } from "@library/components/icons/common";

interface IProps {
    expandAll: () => void;
    collapseAll: () => void;
    newCategory: () => void;
    newCategoryButtonRef: React.RefObject<HTMLButtonElement>;
}

interface IState {
    allExpanded: boolean;
    allCollapsed: boolean;
}

export default class NavigationManagerToolBar extends React.Component<IProps, IState> {
    public state: IState = {
        allExpanded: false,
        allCollapsed: false,
    };

    public render() {
        return (
            <div className="treeToolBar">
                <Button
                    className="treeToolBar-button treeToolBar-expandAll"
                    onClick={this.props.expandAll}
                    disabled={this.state.allExpanded}
                >
                    {expandAll()}
                    {t("Expand All")}
                </Button>
                <Button
                    className="treeToolBar-button treeToolBar-collapseAll"
                    onClick={this.props.collapseAll}
                    disabled={this.state.allCollapsed}
                >
                    {collapseAll()}
                    {t("Collapse All")}
                </Button>
                <Button
                    className="treeToolBar-button treeToolBar-collapseAll"
                    onClick={this.props.newCategory}
                    disabled={this.state.allExpanded}
                    buttonRef={this.props.newCategoryButtonRef}
                >
                    {newFolder()}
                    {t("New Category")}
                </Button>
            </div>
        );
    }
}
