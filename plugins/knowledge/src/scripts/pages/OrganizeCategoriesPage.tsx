/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import NavigationManager, {
    NavigationManager as UnwrappedNavigationManager,
} from "@knowledge/modules/navigation/NavigationManager";
import NavigationManagerMenu from "@knowledge/modules/navigation/NavigationManagerMenu";
import NavigationManagerToolBar from "@knowledge/modules/navigation/NavigationManagerToolBar";
import { t } from "@library/application";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DocumentTitle from "@library/components/DocumentTitle";
import Heading from "@library/components/Heading";
import React from "react";
import { ConnectedComponentClass } from "react-redux";

interface IProps {}

interface IState {
    showNewCategoryModal: boolean;
}

export default class OrganizeCategoriesPage extends React.Component<IProps, IState> {
    private titleID = uniqueIDFromPrefix("organzieCategoriesTitle");
    private newCategoryButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private managerRef = React.createRef();

    public state: IState = {
        showNewCategoryModal: false,
    };

    public render() {
        const pageTitle = t("Navigation Manager");
        return (
            <>
                <FullKnowledgeModal titleID={this.titleID}>
                    <NavigationManagerMenu />
                    <div className="container">
                        <DocumentTitle title={pageTitle}>
                            <Heading depth={1} renderAsDepth={2} className="pageSubTitle" title={pageTitle} />
                        </DocumentTitle>
                        <NavigationManagerToolBar
                            collapseAll={this.collapseAll}
                            expandAll={this.expandAll}
                            newCategory={this.showNewCategoryModal}
                            newCategoryButtonRef={this.newCategoryButtonRef}
                        />
                        <NavigationManager knowledgeBaseID={1} ref={this.managerRef} />
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

    private get manager(): UnwrappedNavigationManager | null {
        return (this.managerRef.current && (this.managerRef.current as any).getWrappedInstance()) || null;
    }

    private expandAll = () => {
        this.manager && this.manager.expandAll();
    };

    private collapseAll = () => {
        this.manager && this.manager.collapseAll();
    };

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
