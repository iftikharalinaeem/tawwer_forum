/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { folderClosed, folderOpen, article } from "@library/components/icons/navigationManager";
import classNames from "classNames";
import { downTriangle, rightTriangle } from "@library/components/icons/common";

interface IProps {
    expanded: boolean;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    itemId: string;
    disabled?: boolean;
    hasChildren: boolean;
    className?: string;
}

export default class NavigationManagerItemIcon extends React.Component<IProps> {
    public render() {
        if (this.props.hasChildren) {
            return (
                <Button
                    onClick={this.handleClick}
                    className={classNames("navigationManager-toggleFolder", this.props.className)}
                    disabled={!!this.props.disabled}
                    baseClass={ButtonBaseClass.CUSTOM}
                >
                    {this.icon()}
                </Button>
            );
        } else {
            return article("navigationManager-articleIcon", "navigationManager-articleIconFill");
        }
    }

    private handleClick = e => {
        const { expanded, expandItem, collapseItem, itemId } = this.props;
        expanded ? collapseItem(itemId) : expandItem(itemId);
    };

    private icon = () => {
        if (this.props.expanded) {
            return (
                <>
                    <span className="navigationManager-triangle">{downTriangle("navigationManager-triangleDown")}</span>
                    {folderOpen("navigationManager-folder navigationManager-folderOpen")}
                </>
            );
        } else {
            return (
                <>
                    <span className="navigationManager-triangle">
                        {rightTriangle("navigationManager-triangleRight")}
                    </span>
                    {folderClosed("navigationManager-folder navigationManager-folderClosed")}
                </>
            );
        }
    };
}
