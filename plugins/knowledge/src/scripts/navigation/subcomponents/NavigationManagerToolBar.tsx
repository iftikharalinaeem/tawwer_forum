/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import { newFolder } from "@library/components/icons/common";
import { collapseAll, expandAll } from "@library/components/icons/navigationManager";
import React from "react";

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
            <div className="navigationManagerToolbar">
                <div className="navigationManagerToolbar-bar">
                    <Button
                        className="navigationManagerToolbar-button navigationManagerToolbar-expandAll"
                        onClick={this.props.expandAll}
                        disabled={this.state.allExpanded}
                    >
                        {expandAll("navigationManagerToolbar-icon")}
                        <span className="navigationManagerToolbar-buttonLabel">{t("Expand All")}</span>
                    </Button>
                    <Button
                        className="navigationManagerToolbar-button navigationManagerToolbar-collapseAll"
                        onClick={this.props.collapseAll}
                        disabled={this.state.allCollapsed}
                    >
                        {collapseAll("navigationManagerToolbar-icon")}
                        <span className="navigationManagerToolbar-buttonLabel">{t("Collapse All")}</span>
                    </Button>
                    <Button
                        className="navigationManagerToolbar-button navigationManagerToolbar-newFolder"
                        onClick={this.props.newCategory}
                        disabled={this.state.allExpanded}
                        buttonRef={this.props.newCategoryButtonRef}
                    >
                        {newFolder("navigationManagerToolbar-icon navigationManagerToolbar-newFolder")}
                        <span className="navigationManagerToolbar-buttonLabel">{t("New Category")}</span>
                    </Button>
                </div>
                <hr className="navigationManagerToolbar-separator" />
            </div>
        );
    }
}
