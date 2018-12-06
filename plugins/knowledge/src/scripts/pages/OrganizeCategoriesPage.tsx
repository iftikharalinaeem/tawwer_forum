/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import NavigationManager from "@knowledge/modules/navigation/NavigationManager";
import NavigationManagerMenu from "@knowledge/modules/navigation/NavigationManagerMenu";
import NavigationManagerToolBar from "@knowledge/modules/navigation/NavigationManagerToolBar";
import { t } from "@library/application";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DocumentTitle from "@library/components/DocumentTitle";
import Heading from "@library/components/Heading";
import React from "react";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import { INavigationStoreState } from "@knowledge/modules/navigation/NavigationModel";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import { connect } from "react-redux";

interface IActions {
    navigationActions: NavigationActions;
    articleActions: ArticleActions;
    categoryActions: CategoryActions;
}

interface IProps extends INavigationStoreState, IActions {}

interface IState {
    showNewCategoryModal: boolean;
}

export class OrganizeCategoriesPage extends React.Component<IProps, IState> {
    private titleID = uniqueIDFromPrefix("organzieCategoriesTitle");
    private newCategoryButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state: IState = {
        showNewCategoryModal: false,
    };

    public render() {
        const pageTitle = t("Navigation Manager");
        console.log(this.props.fetchLoadable.status + "navManager");
        return (
            <>
                <FullKnowledgeModal titleID={this.titleID}>
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
                        <NavigationManager
                            navigationItems={this.props.navigationItems}
                            key={this.props.fetchLoadable.status + "navManager"}
                            updateItems={this.props.navigationActions.patchNavigationFlat}
                        />
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

    public componentDidMount() {
        void this.props.navigationActions.getNavigationFlat({ knowledgeBaseID: 1 });
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

function mapStateToProps(state: IStoreState) {
    return state.knowledge.navigation;
}

function mapDispatchToProps(dispatch): IActions {
    return {
        articleActions: new ArticleActions(dispatch, apiv2),
        navigationActions: new NavigationActions(dispatch, apiv2),
        categoryActions: new CategoryActions(dispatch, apiv2),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(OrganizeCategoriesPage);
