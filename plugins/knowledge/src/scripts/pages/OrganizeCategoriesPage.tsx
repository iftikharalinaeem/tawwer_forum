/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import DocumentTitle from "@library/components/DocumentTitle";
import NavigationManager from "@knowledge/modules/navigation/NavigationManager";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import NavigationManagerToolBar from "@knowledge/modules/navigation/NavigationManagerToolBar";
import { Modal } from "@library/components/modal";
import { ILPActionsProps } from "@knowledge/modules/locationPicker/LocationPickerActions";
import { ILPConnectedData } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { ILocationInputProps } from "@knowledge/modules/locationPicker/LocationInput";
import { LocationInput } from "@knowledge/modules/locationPicker/LocationInput";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import NavigationManagerMenu from "@knowledge/modules/navigation/NavigationManagerMenu";
import Heading from "@library/components/Heading";
interface IProps extends ILocationInputProps {}

interface IState {
    showNewCategoryModal: boolean;
}

export default class OrganizeCategoriesPage extends React.Component<IProps, IState> {
    private titleID = uniqueIDFromPrefix("organzieCategoriesTitle");
    private newCategoryButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state: IState = {
        showNewCategoryModal: false,
    };

    public render() {
        const pageTitle = t("Navigation Manager");
        return (
            <>
                <FullKnowledgeModal titleID={this.titleID} className={this.props.className}>
                    <NavigationManagerMenu />
                    <div className="container">
                        <DocumentTitle title={pageTitle}>
                            <Heading depth={1} renderAsDepth={2} className="pageSubTitle" title={pageTitle} />
                        </DocumentTitle>
                        <NavigationManagerToolBar
                            collapseAll={this.todo}
                            expandAll={this.todo}
                            newCategory={this.showNewCategoryModal}
                            newCategoryButtonRef={this.newCategoryButtonRef}
                        />
                        <NavigationManager />
                    </div>
                </FullKnowledgeModal>
                {this.state.showNewCategoryModal && (
                    <NewCategoryForm
                        exitHandler={this.hideNewFolderModal}
                        parentCategory={null}
                        buttonRef={this.newCategoryButtonRef}
                    />
                )}
            </>
        );
    }

    /**
     * Show the location picker modal.
     */
    private showNewCategoryModal = () => {
        this.setState({
            showNewCategoryModal: true,
        });
    };

    /**
     * Hiders the location picker modal.
     */
    private hideNewFolderModal = e => {
        e.stopPropagation();
        this.setState({
            showNewCategoryModal: false,
        });
        // this.handleChoose(e);
    };

    public componentDidUpdate(prevProps, prevState) {
        if (prevState.showNewCategoryModal !== this.state.showNewCategoryModal) {
            this.forceUpdate();
        }
    }

    private handleChoose = e => {
        e.stopPropagation();
        this.hideNewFolderModal(e);
    };

    public todo = () => {
        alert("To do!");
    };
}
