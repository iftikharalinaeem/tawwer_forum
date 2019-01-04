/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { downTriangle, rightTriangle } from "@library/components/icons/common";
import { article, folderClosed, folderOpen } from "@library/components/icons/navigationManager";
import classNames from "classnames";
import React from "react";
import { NavigationRecordType } from "@knowledge/@types/api";
import FlexSpacer from "@library/components/FlexSpacer";

interface IProps {
    expanded: boolean;
    expandItem: () => void;
    collapseItem: () => void;
    itemId: string;
    disabled?: boolean;
    type: NavigationRecordType;
    className?: string;
    hasChildren: boolean;
}

export default class NavigationManagerItemIcon extends React.Component<IProps> {
    public render() {
        const spacerClassName = classNames("navigationManager-toggleSpacer");
        if (this.props.type === NavigationRecordType.KNOWLEDGE_CATEGORY) {
            const className = classNames("navigationManager-toggleFolder", this.props.className);
            if (this.props.hasChildren) {
                return (
                    <Button
                        onClick={this.handleClick}
                        className={className}
                        disabled={!!this.props.disabled}
                        baseClass={ButtonBaseClass.CUSTOM}
                        tabIndex={-1}
                    >
                        {this.icon()}
                    </Button>
                );
            } else {
                return (
                    <span className={className}>
                        <FlexSpacer className={spacerClassName} />
                        {this.icon()}
                    </span>
                );
            }
        } else {
            return (
                <span className="navigationManager-articlePage">
                    <FlexSpacer className={spacerClassName} />
                    {article("navigationManager-articleIcon", "navigationManager-articleIconFill")}
                </span>
            );
        }
    }

    private handleClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        const { expanded, expandItem, collapseItem } = this.props;
        expanded ? collapseItem() : expandItem();
    };

    private icon = () => {
        if (this.props.expanded) {
            return (
                <>
                    <span className="navigationManager-triangle">
                        {this.props.hasChildren && downTriangle("navigationManager-triangleDown")}
                    </span>
                    <span className="navigationManager-folderIcon">
                        {folderOpen("navigationManager-folder navigationManager-folderOpen")}
                    </span>
                </>
            );
        } else {
            return (
                <>
                    <span className="navigationManager-triangle">
                        {this.props.hasChildren && rightTriangle("navigationManager-triangleRight")}
                    </span>
                    <span className="navigationManager-folderIcon">
                        {folderClosed("navigationManager-folder navigationManager-folderClosed")}
                    </span>
                </>
            );
        }
    };
}
