/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import * as React from "react";
import { t } from "@library/application";
import { ButtonBaseClass } from "@library/components/forms/Button";
import DropDown from "@library/components/dropdown/DropDown";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import DropDownItemLink from "@library/components/dropdown/items/DropDownItemLink";
import ModalConfirm from "@library/components/modal/ModalConfirm";
import { connect } from "react-redux";
import { DraftsPage } from "@knowledge/modules/drafts/DraftsPage";
import DraftsPageModel from "@knowledge/modules/drafts/DraftsPageModel";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import apiv2 from "@library/apiv2";
import { IInjectableDraftsPageProps } from "@knowledge/modules/drafts/DraftsPageModel";
import { LoadStatus } from "@library/@types/api";

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
                    buttonClassName={ButtonBaseClass.CUSTOM}
                    renderLeft={true}
                    setExternalButtonRef={this.setButtonRef}
                    toggleButtonClassName="draftPreview-actionsToggle"
                    className="draftPreview-actions"
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
