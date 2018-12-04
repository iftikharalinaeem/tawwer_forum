/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { RefObject } from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { folderClosed, folderOpen, article } from "@library/components/icons/tree";
import classNames from "classNames";
import { downTriangle, rightTriangle } from "@library/components/icons/common";
import { ICurrentCategory } from "@knowledge/modules/navigation/NavigationManager";
import { t } from "library/src/scripts/application";

interface IProps {
    className?: string;
    disabled?: boolean;
    item: ICurrentCategory;
    deleteItem: (item: ICurrentCategory, deleteButtonRef: React.RefObject<HTMLButtonElement>) => void;
}

export default class NavigationManagerDelete extends React.Component<IProps> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    public render() {
        return (
            <Button
                onClick={this.handleClick}
                className={classNames("navigationManager-delete", this.props.className)}
                disabled={!!this.props.disabled}
                baseClass={ButtonBaseClass.ICON}
                buttonRef={this.buttonRef}
            >
                {t("Delete")}
            </Button>
        );
    }

    private handleClick = () => {
        this.props.deleteItem(this.props.item, this.buttonRef);
    };
}
