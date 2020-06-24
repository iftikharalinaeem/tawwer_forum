/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import FlexSpacer from "@library/layout/FlexSpacer";
import Button from "@library/forms/Button";
import { article, folderClosed, folderOpen } from "@knowledge/navigation/navigationManagerIcons";
import classNames from "classnames";
import React from "react";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DownTriangleIcon, RightTriangleIcon } from "@library/icons/common";

interface IProps {
    expanded: boolean;
    expandItem: () => void;
    collapseItem: () => void;
    itemId: string;
    disabled?: boolean;
    type: KbRecordType;
    className?: string;
    hasChildren: boolean;
}

export default class NavigationManagerItemIcon extends React.Component<IProps> {
    public render() {
        const classesNavigatoionManager = navigationManagerClasses();
        const spacerClassName = classNames("navigationManager-toggleSpacer", classesNavigatoionManager.toggleSpacer);
        if (this.props.type === KbRecordType.CATEGORY) {
            const className = classNames(
                "navigationManager-toggleFolder",
                classesNavigatoionManager.toggleFolder,
                this.props.className,
            );
            if (this.props.hasChildren) {
                return (
                    <Button
                        onClick={this.handleClick}
                        className={className}
                        disabled={!!this.props.disabled}
                        baseClass={ButtonTypes.CUSTOM}
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
            const classesNavigationManager = navigationManagerClasses();
            return (
                <span
                    className={classNames(
                        "navigationManager-articlePage",
                        { isChild: this.props.expanded },
                        classesNavigationManager.articlePage,
                    )}
                >
                    <FlexSpacer className={spacerClassName} />
                    {article(
                        classNames("navigationManager-articleIcon", classesNavigationManager.articleIcon),
                        classNames("navigationManager-articleIconFill", classesNavigationManager.articleIconFill),
                    )}
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
        const classes = navigationManagerClasses();
        if (this.props.expanded) {
            return (
                <>
                    {this.props.hasChildren && (
                        <span className={classNames("navigationManager-triangle", classes.triangle)}>
                            <DownTriangleIcon className={"navigationManager-triangleDown"} />
                        </span>
                    )}
                    <span className={classNames("navigationManager-folderIcon", classes.folderIcon)}>
                        {folderOpen(
                            classNames("navigationManager-folder", "navigationManager-folderOpen", classes.folder),
                        )}
                    </span>
                </>
            );
        } else {
            return (
                <>
                    <span className={classNames("navigationManager-triangle", classes.triangle)}>
                        {this.props.hasChildren && <RightTriangleIcon className={"navigationManager-triangleRight"} />}
                    </span>
                    <span className={classNames("navigationManager-folderIcon", classes.folderIcon)}>
                        {folderClosed(
                            classNames("navigationManager-folder", "navigationManager-folderClosed", classes.folder),
                        )}
                    </span>
                </>
            );
        }
    };
}
