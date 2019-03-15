/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import DraftsPageModel, { IInjectableDraftsPageProps } from "@knowledge/modules/drafts/DraftsPageModel";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import DropDown from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import * as React from "react";
import { connect } from "react-redux";
import { ButtonTypes } from "@library/forms/buttonStyles";

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
        return (
            <React.Fragment>
                <DropDown
                    name={t("Draft Options")}
                    buttonClassName={ButtonTypes.CUSTOM}
                    renderLeft={true}
                    setExternalButtonRef={this.setButtonRef}
                    toggleButtonClassName="draftPreview-actionsToggle"
                    className="draftPreview-actions"
                    paddedList={true}
                >
                    <DropDownItemLink name={t("Edit")} to={this.props.url} className="draftPreview-option" />
                    <DropDownItemButton
                        name={t("Delete")}
                        onClick={this.openDeleteDialogue}
                        className="draftPreview-option"
                    />
                </DropDown>
                {this.state.showDeleteDialogue && (
                    <ModalConfirm
                        title={t("Delete Draft")}
                        onCancel={this.closeDeleteDialogue}
                        onConfirm={this.deleteDraft}
                        elementToFocusOnExit={this.toggleButtonRef.current! as HTMLElement}
                        isConfirmLoading={this.props.deleteDraft.status === LoadStatus.LOADING}
                    >
                        {t("This is a destructive action. You will not be able to restore your draft.")}
                    </ModalConfirm>
                )}
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

    /*
     * Set reference to button we need to target
     */
    private setButtonRef = (ref: React.RefObject<HTMLButtonElement>) => {
        this.toggleButtonRef = ref;
        this.forceUpdate();
    };
}

function mapDispatchToProps(dispatch) {
    return {
        actions: new ArticleActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    DraftsPageModel.mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(DraftMenu);
