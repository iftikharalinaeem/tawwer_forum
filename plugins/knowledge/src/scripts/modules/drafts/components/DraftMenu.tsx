/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import DraftsPageModel, { IInjectableDraftsPageProps } from "@knowledge/modules/drafts/DraftsPageModel";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import * as React from "react";
import { connect } from "react-redux";
import { ButtonTypes } from "@library/forms/buttonTypes";
import classNames from "classnames";
import { draftPreviewClasses } from "@knowledge/modules/drafts/components/DraftPreviewStyles";

interface IProps extends IInjectableDraftsPageProps {
    actions: ArticleActions;
    className?: string;
    draftID: number;
    url: string;
}

interface IState {
    showDeleteDialogue: boolean;
}

/**
 * Implements actions to take on draft
 */
export class DraftMenu extends React.Component<IProps, IState> {
    private toggleButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public constructor(props) {
        super(props);
        this.state = {
            showDeleteDialogue: false,
        };
    }

    public render() {
        const classes = draftPreviewClasses();
        return (
            <React.Fragment>
                <DropDown
                    name={t("Draft Options")}
                    buttonClassName={ButtonTypes.CUSTOM}
                    renderLeft={true}
                    buttonRef={this.toggleButtonRef}
                    toggleButtonClassName={classes.toggle}
                    className={classNames(classes.actions, this.props.className)}
                    flyoutType={FlyoutType.LIST}
                >
                    <DropDownItemLink name={t("Edit")} to={this.props.url} className={classes.option} />
                    <DropDownItemButton
                        name={t("Delete")}
                        onClick={this.openDeleteDialogue}
                        className="draftPreview-option"
                    />
                </DropDown>

                <ModalConfirm
                    isVisible={this.state.showDeleteDialogue}
                    title={t("Delete Draft")}
                    onCancel={this.closeDeleteDialogue}
                    onConfirm={this.deleteDraft}
                    elementToFocusOnExit={this.toggleButtonRef.current! as HTMLElement}
                    isConfirmLoading={this.props.deleteDraft.status === LoadStatus.LOADING}
                >
                    {t("This is a destructive action. You will not be able to restore your draft.")}
                </ModalConfirm>
            </React.Fragment>
        );
    }

    private deleteDraft = async () => {
        await this.props.actions.deleteDraft({ draftID: this.props.draftID });
        this.closeDeleteDialogue();
    };

    /**
     * Open the delete dialogue.
     */
    private openDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: true });
    };

    /**
     * Close the delete dialogue.
     */
    private closeDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: false });
    };
}

function mapDispatchToProps(dispatch) {
    return {
        actions: new ArticleActions(dispatch, apiv2),
    };
}

const withRedux = connect(DraftsPageModel.mapStateToProps, mapDispatchToProps);

export default withRedux(DraftMenu);
