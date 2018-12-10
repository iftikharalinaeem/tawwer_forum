/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import Tree, {
    IRenderItemParams,
    ITreeData,
    ITreeDestinationPosition,
    ITreeItem,
    ITreeSourcePosition,
    moveItemOnTree,
    mutateTree,
} from "@atlaskit/tree";
import { NavigationRecordType, ArticleStatus, IKbNavigationItem } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import NavigationManagerContent from "@knowledge/modules/navigation/NavigationManagerContent";
import NavigationManagerToolBar from "@knowledge/modules/navigation/NavigationManagerToolBar";
import NavigationModel, {
    INavigationStoreState,
    INormalizedNavigationItem,
    INormalizedNavigationItems,
} from "@knowledge/modules/navigation/NavigationModel";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import classNames from "classnames";
import React from "react";
import { connect } from "react-redux";
import { ModalConfirm } from "@library/components/modal";
import Translate from "@library/components/translation/Translate";
import { t } from "@library/application";

interface IProps extends IActions, INavigationStoreState {
    className?: string;
    navigationItems: INormalizedNavigationItems;
    knowledgeBaseID: number;
}

interface IState {
    treeData: ITreeData<INormalizedNavigationItem>;
    selectedItem: ITreeItem<INormalizedNavigationItem> | null;
    deleteItem: ITreeItem<INormalizedNavigationItem> | null;
    disabled: boolean;
    showNewCategoryModal: boolean;
    writeMode: boolean;
    elementToFocusOnDeleteClose: HTMLButtonElement | null;
}

export class NavigationManager extends React.Component<IProps, IState> {
    private self: React.RefObject<HTMLDivElement> = React.createRef();
    private newCategoryButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state: IState = {
        treeData: this.calcTree(),
        selectedItem: null,
        deleteItem: null,
        disabled: false,
        showNewCategoryModal: false,
        writeMode: false,
        elementToFocusOnDeleteClose: null,
    };

    /**
     * @inheritdoc
     */
    public render() {
        return (
            <>
                <NavigationManagerToolBar
                    collapseAll={this.collapseAll}
                    expandAll={this.expandAll}
                    newCategory={this.showNewCategoryModal}
                    newCategoryButtonRef={this.newCategoryButtonRef}
                />
                <div ref={this.self} className={classNames("navigationManager", this.props.className)}>
                    <Tree
                        tree={this.state.treeData}
                        onDragEnd={this.onDragEnd}
                        onCollapse={this.collapseItem}
                        onExpand={this.expandItem}
                        renderItem={this.renderItem}
                        isDragEnabled={!this.state.disabled}
                        key={`${this.state.selectedItem ? this.state.selectedItem.id : undefined}-${
                            this.state.writeMode
                        }`}
                    />
                </div>
                {this.renderNewCategoryModal()}
                {this.renderDeleteModal()}
            </>
        );
    }

    /**
     * Render callback for @atlaskit/tree. Render's a single navigation item.
     */
    private renderItem = (params: IRenderItemParams<INormalizedNavigationItem>) => {
        const { provided, item, snapshot } = params;
        const hasChildren = item.children && item.children.length > 0;
        const deleteHandler = (focusButton: HTMLButtonElement) => {
            this.setState({ elementToFocusOnDeleteClose: focusButton });
            this.showDeleteModal(item);
        };
        return (
            <NavigationManagerContent
                item={item}
                snapshot={snapshot}
                provided={provided}
                hasChildren={hasChildren}
                onRenameSubmit={this.handleRename}
                expandItem={this.expandItem}
                collapseItem={this.collapseItem}
                selectedItem={this.state.selectedItem}
                selectItem={this.selectItem}
                writeMode={this.state.writeMode}
                onDeleteClick={deleteHandler}
            />
        );
    };

    /**
     * @inheritdoc
     */
    public async componentDidMount() {
        const { knowledgeBaseID } = this.props;
        await this.props.navigationActions.getNavigationFlat({ knowledgeBaseID });
    }

    /**
     * @inheritdoc
     */
    public componentDidUpdate(prevProps: IProps) {
        if (this.props.navigationItems !== prevProps.navigationItems) {
            this.setState({ treeData: this.calcTree() });
        }
    }

    /**
     * Collapse all items in the tree.
     */
    private collapseAll = () => {
        this.updateAllItems({ isExpanded: false });
    };

    /**
     * Expand all items in the tree.
     */
    private expandAll = () => {
        this.updateAllItems({ isExpanded: true });
    };

    /**
     * Expand a single item.
     */
    private expandItem = (itemId: string) => {
        const { treeData } = this.state;
        this.setState({
            treeData: mutateTree(treeData, itemId, { isExpanded: true }),
        });
    };

    /**
     * Collapse a single item.
     */
    private collapseItem = (itemId: string) => {
        const { treeData } = this.state;
        this.setState({
            treeData: mutateTree(treeData, itemId, { isExpanded: false }),
        });
    };

    /**
     * Handle the rename of a navigatio item.
     */
    private handleRename = (item: IKbNavigationItem, newName: string) => {
        switch (item.recordType) {
            case NavigationRecordType.KNOWLEDGE_CATEGORY:
                void this.props.categoryActions.patchCategory({ knowledgeCategoryID: item.recordID, name: newName });
                break;
            case NavigationRecordType.ARTICLE:
                void this.props.articleActions.patchArticle({ articleID: item.recordID, name: newName });
                break;
        }

        this.clearSelectedItem();
    };

    /**
     * Select a single item. Takes an optional callback for after the state has been updated.
     */
    private selectItem = (
        selectedItem: ITreeItem<INormalizedNavigationItem>,
        writeMode: boolean = false,
        callback?: () => void,
    ) => {
        this.expandItem(selectedItem.id);
        this.setState(
            {
                selectedItem,
                writeMode,
            },
            callback,
        );
    };

    /**
     * Reset the selected item.
     */
    private clearSelectedItem = () => {
        this.setState({
            selectedItem: null,
        });
    };

    /// MODALS

    /**
     * Get the currently "selected" category. If we have an article selected use it's parent category.
     */
    private get currentTargetCategoryID(): number {
        const { selectedItem } = this.state;

        if (!selectedItem) {
            // This should be the category assosciated with the knowledge base once hooked up.
            return this.props.knowledgeBaseID;
        }

        if (selectedItem.data.recordType === NavigationRecordType.ARTICLE) {
            return selectedItem.data.parentID;
        } else {
            return selectedItem.data.recordID;
        }
    }

    /**
     * Render function for the new category modal.
     */
    private renderNewCategoryModal(): React.ReactNode {
        return (
            this.state.showNewCategoryModal && (
                <NewCategoryForm
                    exitHandler={this.hideNewFolderModal}
                    parentCategoryID={this.currentTargetCategoryID}
                    buttonRef={this.newCategoryButtonRef}
                    onSuccessfulSubmit={this.onNewCategorySuccess}
                />
            )
        );
    }

    /**
     * Handler for the when a new category has been successfully created.
     *
     * - Re-fetches the navigation tree.
     */
    private onNewCategorySuccess = async () => {
        const { knowledgeBaseID } = this.props;
        await this.props.navigationActions.getNavigationFlat({ knowledgeBaseID }, true);
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
    private hideNewFolderModal = () => {
        this.setState({
            showNewCategoryModal: false,
        });
    };

    /// DELETE MODAL

    /**
     * Render method for the delete category modal.
     */
    private renderDeleteModal(): React.ReactNode {
        const { deleteItem } = this.state;
        return (
            deleteItem && (
                <ModalConfirm
                    title={(<Translate source={'Delete "<0/>"'} c0={deleteItem.data.name} /> as unknown) as string}
                    onCancel={this.dismissDeleteModal}
                    onConfirm={this.handleDeleteConfirm}
                    elementToFocusOnExit={this.state.elementToFocusOnDeleteClose || document.body}
                >
                    <Translate
                        source={'Are you sure you want to delete <0/> "<1/>" ?'}
                        c0={this.getItemTypeLabel(deleteItem.data)}
                        c1={
                            <strong>
                                <em>{deleteItem.data.name}</em>
                            </strong>
                        }
                    />
                </ModalConfirm>
            )
        );
    }

    /**
     * Handle confirmation that an item should be deleted.
     *
     * Updates either an article of category using their respective endpoints.
     */
    private handleDeleteConfirm = async () => {
        const { deleteItem } = this.state;
        if (deleteItem) {
            const { recordType, recordID } = deleteItem.data;
            switch (recordType) {
                case NavigationRecordType.ARTICLE:
                    await this.props.articleActions.patchStatus({ articleID: recordID, status: ArticleStatus.DELETED });
                    break;
                case NavigationRecordType.KNOWLEDGE_CATEGORY:
                    await this.props.categoryActions.deleteCategory(recordID);
                    break;
            }
        }
        this.dismissDeleteModal();
    };

    /**
     * Display the delete modal.
     */
    private showDeleteModal = (item: ITreeItem<INormalizedNavigationItem>) => {
        this.setState({ deleteItem: item });
        this.disableTree();
        this.selectItem(item, false);
    };

    /**
     * Dismiss the delete modal.
     */
    private dismissDeleteModal = () => {
        const { deleteItem } = this.state;
        this.setState({
            deleteItem: null,
            elementToFocusOnDeleteClose: null,
        });
        this.enableTree();
        if (deleteItem) {
            this.selectItem(deleteItem, false);
        }
    };

    /**
     * The label of the current type.
     */
    private getItemTypeLabel(item: INormalizedNavigationItem): string {
        const { recordType } = item;
        switch (recordType) {
            case NavigationRecordType.ARTICLE:
                return t("article");
            case NavigationRecordType.KNOWLEDGE_CATEGORY:
                return t("category");
            default:
                return recordType;
        }
    }

    /**
     * Disable editing of the whole tree. Takes an optional callback for when the state update has completed.
     */
    private disableTree = () => {
        this.setState({ disabled: true });
    };

    /**
     * Enable editing of the whole tree. Takes an optional callback for when the state update has completed.
     */
    private enableTree = () => {
        this.setState({ disabled: false });
    };

    /**
     * Handle completion of drag.
     *
     * - Update item in local state, and additionally dispatch to the API endoint.
     */
    private onDragEnd = async (source: ITreeSourcePosition, destination?: ITreeDestinationPosition) => {
        const { treeData } = this.state;

        if (!destination) {
            return;
        }
        // Do nothing if we leave it in the spot we started.
        if (source.index === destination.index && source.parentId === destination.parentId) {
            return;
        }

        const newTree = moveItemOnTree(treeData, source, destination);
        this.setState({
            treeData: newTree,
            selectedItem: newTree.items[source.parentId].children[source.index],
        });
        await this.props.navigationActions.patchNavigationFlat(this.calcPatchArray(this.state.treeData));
    };

    /**
     * Update all of the items in the tree with the same data partial.
     *
     * @param update
     */
    private updateAllItems(update: Partial<ITreeItem<INormalizedNavigationItem>>) {
        const data: ITreeData<INormalizedNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(this.state.treeData.items)) {
            const newData = update.data || {};
            data.items[itemID] = {
                ...itemValue,
                ...update,
                data: {
                    ...itemValue.data,
                    ...newData,
                },
            };
        }

        this.setState({ treeData: data });
    }

    /**
     * Take the internal tree state and convert back to a pure data array for patching the API endpoint.
     */
    private calcPatchArray(data: ITreeData<INormalizedNavigationItem>) {
        const outOfTree = {};
        for (const [index, value] of Object.entries(data.items)) {
            outOfTree[index] = {
                ...value.data,
                children: value.children,
            };
        }
        return NavigationModel.denormalizeData(outOfTree, "knowledgeCategory1");
    }

    /**
     * Convert the pure data representation of the tree data into one that contains UI state.
     *
     * - Makes ITreeData items.
     * - Preserves the existing IDs
     * - Preserves existing expand state if it exists.
     */
    private calcTree() {
        const data: ITreeData<INormalizedNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(this.props.navigationItems)) {
            let stateValue: ITreeItem<INormalizedNavigationItem> | null = null;
            if (this.state && this.state.treeData.items[itemID]) {
                stateValue = this.state.treeData.items[itemID];
            }

            const children = itemValue.children;
            data.items[itemID] = {
                id: itemID,
                hasChildren: children.length > 0,
                children,
                data: itemValue,
                isExpanded: stateValue ? stateValue.isExpanded : false,
            };
        }

        return data;
    }
}

interface IActions {
    navigationActions: NavigationActions;
    articleActions: ArticleActions;
    categoryActions: CategoryActions;
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
const Connected = connect(
    mapStateToProps,
    mapDispatchToProps,
)(NavigationManager);

export default Connected;
