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
import { t } from "@library/application";
import ModalConfirm from "@library/components/modal/ModalConfirm";
import Translate from "@library/components/translation/Translate";
import { IKbNavigationItem } from "@knowledge/@types/api/kbNavigation";
import { ITreeItem } from "@atlaskit/tree";

interface IProps {
    className?: string;
    disabled?: boolean;
    item: ITreeItem;
    deleteItem: (item: ITreeItem, deleteButtonRef: React.RefObject<HTMLButtonElement>, callback: () => void) => void;
}

interface IState {
    delete: boolean;
}

export default class NavigationManagerDelete extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    public state = {
        delete: false,
    };
    public render() {
        const { name, recordID, recordType } = this.props.item.data;
        return (
            <>
                <Button
                    onClick={this.showConfirmation}
                    className={classNames("navigationManager-delete", this.props.className)}
                    disabled={!!this.props.disabled}
                    baseClass={ButtonBaseClass.ICON}
                    buttonRef={this.buttonRef}
                >
                    {t("Delete")}
                </Button>
                {this.state.delete && (
                    <ModalConfirm
                        title={(<Translate source={'Delete "<0/>"'} c0={name} /> as unknown) as string}
                        onCancel={this.closeConfirmation}
                        onConfirm={this.handleDelete}
                        elementToFocusOnExit={this.buttonRef.current!}
                    >
                        <Translate
                            source={'Are you sure you want to delete <0/>: "<1/>"'}
                            c0={this.getType(recordType)}
                            c1={name}
                        />
                    </ModalConfirm>
                )}
            </>
        );
    }

    private closeConfirmation = () => {
        this.setState({
            delete: false,
        });
    };
    private showConfirmation = () => {
        this.setState({
            delete: true,
        });
    };

    private handleDelete = () => {
        this.props.deleteItem(this.props.item, this.buttonRef, () => {
            this.setState({
                delete: false,
            });
        });
    };

    private getType = (type: string) => {
        switch (type) {
            case "article":
                return t("article");
            case "knowledgeCategory":
                return t("category");
            default:
                return type;
        }
    };
}
