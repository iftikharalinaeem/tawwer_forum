/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { folderClosed, folderOpen, article } from "@library/components/icons/tree";
import classNames from "classNames";

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
                    baseClass={ButtonBaseClass.ICON}
                >
                    {this.props.expanded ? folderOpen() : folderClosed()}
                </Button>
            );
        } else {
            return article();
        }
    }

    private handleClick = e => {
        const { expanded, expandItem, collapseItem, itemId } = this.props;
        expanded ? collapseItem(itemId) : expandItem(itemId);
    };
}
