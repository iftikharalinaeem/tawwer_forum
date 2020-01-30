/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IRenderItemParams,
    ITreeData,
    ITreeDestinationPosition,
    ITreeItem,
    ITreeSourcePosition,
    moveItemOnTree,
    mutateTree,
    IDragUpdate,
} from "@atlaskit/tree";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import NavigationModel, {
    IKbNavigationItem,
    INavigationStoreState,
    INormalizedNavigationItem,
    INormalizedNavigationItems,
    KbRecordType,
} from "@knowledge/navigation/state/NavigationModel";
import NavigationManagerContent from "@knowledge/navigation/subcomponents/NavigationManagerContent";
import NavigationManagerToolBar from "@knowledge/navigation/subcomponents/NavigationManagerToolBar";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import Translate from "@library/content/Translate";
import classNames from "classnames";
import React from "react";
import { connect } from "react-redux";
import { IKnowledgeBase, KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import ModalConfirm from "@library/modal/ModalConfirm";
import { PublishStatus } from "@library/@types/api/core";
import CombineAwareTree from "@knowledge/navigation/CombineAwareTree";
import { getCurrentLocale } from "@vanilla/i18n";

interface IProps extends IActions, INavigationStoreState {
    className?: string;
    navigationItems: INormalizedNavigationItems;
    knowledgeBase: IKnowledgeBase;
    describedBy?: string;
}

interface IState {
    treeData: ITreeData<INormalizedNavigationItem>;
    selectedItem: ITreeItem<INormalizedNavigationItem> | null;
    deleteItem: ITreeItem<INormalizedNavigationItem> | null;
    disabled: boolean;
    showNewCategoryModal: boolean;
    writeMode: boolean;
    elementToFocusOnDeleteClose: HTMLButtonElement | null;
    dragging: boolean;
    allowCombining: boolean;
}

export class NavigationManager extends React.Component<IProps, IState> {
    private self: React.RefObject<HTMLDivElement> = React.createRef();
    private newCategoryButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private id = uniqueIDFromPrefix("tree");

    public state: IState = {
        treeData: this.calcTree(),
        selectedItem: null,
        deleteItem: null,
        disabled: false,
        showNewCategoryModal: false,
        writeMode: false,
        elementToFocusOnDeleteClose: null,
        dragging: false,
        allowCombining: true,
    };

    /**
     * @inheritdoc
     */
    public render() {
        const classesNavigationManager = navigationManagerClasses();
        const sourceLocale = this.props.knowledgeBase.sourceLocale;
        const canEdit = sourceLocale == getCurrentLocale();

        return (
            <>
                <NavigationManagerToolBar
                    collapseAll={this.collapseAll}
                    expandAll={this.expandAll}
                    newCategory={this.showNewCategoryModal}
                    newCategoryButtonRef={this.newCategoryButtonRef}
                    newCategoryButtonDisable={!canEdit}
                    sourceLocale={sourceLocale}
                />
                <div
                    ref={this.self}
                    className={classNames(
                        "navigationManager",
                        classesNavigationManager.root,
                        inheritHeightClass(),
                        this.props.className,
                    )}
                    role="tree"
                    aria-describedby={this.props.describedBy}
                    onKeyDown={this.handleKeyDown}
                >
                    <CombineAwareTree
                        tree={this.state.treeData}
                        onDragEnd={this.onDragEnd}
                        onDragStart={this.onDragStart}
                        onDragUpdate={this.onDragUpdate}
                        onCollapse={this.collapseItem}
                        onExpand={this.expandItem}
                        renderItem={this.renderItem}
                        isDragEnabled={!this.state.disabled}
                        offsetPerLevel={24}
                        isNestingEnabled={this.state.allowCombining}
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
        const canEdit = this.props.knowledgeBase.sourceLocale == getCurrentLocale();
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
                firstID={this.getFirstItemID()}
                getItemID={this.getItemId}
                isInRoot={this.isItemInRoot(item.parentID)}
                isDeletedDisabled={!canEdit || item.children.length !== 0}
                isRenameDisabled={!canEdit}
            />
        );
    };

    private isItemInRoot = (itemParentID: string) => {
        const rootId = KbRecordType.CATEGORY + this.props.knowledgeBase.rootCategoryID;

        return this.props.knowledgeBase.knowledgeBaseID !== undefined && itemParentID === rootId;
    };

    /**
     * @inheritdoc
     */
    public async componentDidMount() {
        const { knowledgeBaseID } = this.props.knowledgeBase;

        await this.props.navigationActions.getNavigationFlat(knowledgeBaseID);
        await this.props.navigationActions.getTranslationSourceNavigationItems(knowledgeBaseID);
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
        this.setState(
            {
                treeData: mutateTree(treeData, itemId, { isExpanded: true }),
            },
            () => {
                this.selectItemByID(itemId);
            },
        );
    };

    private get rootNavigationItemID() {
        return KbRecordType.CATEGORY + this.props.knowledgeBase.rootCategoryID;
    }

    /**
     * Get the id of the first element in the tree to focus it.
     */
    private getFirstItemID = (): string | null => {
        const { items } = this.state.treeData;
        const rootItem = items[this.rootNavigationItemID];
        if (rootItem && rootItem.children.length > 0) {
            // Hard coded until we
            return rootItem.children[0];
        } else {
            return null;
        }
    };

    /**
     * Get the id of the last element in the tree to focus it.
     */
    private getLastItemID = (): string | null => {
        const { items } = this.state.treeData;
        const rootItem = items[this.rootNavigationItemID];
        if (rootItem && rootItem.children.length > 0) {
            return rootItem.children[rootItem.children.length - 1];
        } else {
            return null;
        }
    };

    private getNextSiblingID(item: ITreeItem<INormalizedNavigationItem>): string | null {
        const { treeData } = this.state;
        const parent = treeData.items[this.calcParentID(item)];
        if (parent) {
            const indexInChildren = parent.children.indexOf(item.id);
            const lastIndex = parent.children.length - 1;
            if (indexInChildren >= 0 && indexInChildren < lastIndex) {
                return parent.children[indexInChildren + 1];
            }
        }

        return null;
    }

    private getPrevSiblingID(item: ITreeItem<INormalizedNavigationItem>): string | null {
        const { treeData } = this.state;
        const parent = treeData.items[this.calcParentID(item)];
        if (parent) {
            const indexInChildren = parent.children.indexOf(item.id);
            if (indexInChildren > 0) {
                return parent.children[indexInChildren - 1];
            }
        }

        return null;
    }

    private getNextFlatID(item: ITreeItem<INormalizedNavigationItem>, checkChildren = true): string | null {
        if (checkChildren && item.children.length > 0 && item.isExpanded) {
            return item.children[0];
        } else {
            const siblingID = this.getNextSiblingID(item);
            if (siblingID) {
                return siblingID;
            } else {
                const parentID = this.calcParentID(item);
                const parent = this.state.treeData.items[parentID];
                if (parent) {
                    return this.getNextFlatID(parent, false);
                }
            }
        }

        return null;
    }

    private getPrevFlatID(item: ITreeItem<INormalizedNavigationItem>): string | null {
        const prevSiblingID = this.getPrevSiblingID(item);

        if (!prevSiblingID) {
            return this.calcParentID(item);
        }
        const prevSibling = this.state.treeData.items[prevSiblingID];
        if (prevSibling) {
            return this.getLastLeafID(prevSibling);
        }
        return null;
    }

    private getLastLeafID(item: ITreeItem<INormalizedNavigationItem>): string {
        if (item.children.length > 0 && item.isExpanded) {
            const lastChildID = item.children[item.children.length - 1];
            const lastChild = this.state.treeData.items[lastChildID];
            if (lastChild) {
                return this.getLastLeafID(lastChild);
            }
        }
        return item.id;
    }

    private calcParentID(item: ITreeItem<INormalizedNavigationItem>): string {
        return KbRecordType.CATEGORY + item.data.parentID;
    }

    private selectItemByID = (itemID: string) => {
        const item = this.state.treeData.items[itemID];
        if (item) {
            this.selectItem(item);
        }
    };
    /**
     * Select a single item. Takes an optional callback for after the state has been updated.
     */
    private selectItem = (selectedItem: ITreeItem<INormalizedNavigationItem>, writeMode: boolean = false) => {
        const newID = selectedItem.id;
        const { treeData, selectedItem: oldSelectedItem } = this.state;
        const oldID = oldSelectedItem ? oldSelectedItem.id : null;

        let nextTree = mutateTree(treeData, newID, {});
        if (oldID) {
            nextTree = mutateTree(nextTree, oldID, {});
        }

        this.setState({
            treeData: nextTree,
            disabled: writeMode,
            selectedItem,
            writeMode,
        });
    };

    /**
     * Collapse a single item.
     */
    private collapseItem = (itemId: string) => {
        const { treeData } = this.state;
        this.setState(
            {
                treeData: mutateTree(treeData, itemId, { isExpanded: false }),
            },
            () => {
                this.selectItemByID(itemId);
            },
        );
    };

    /**
     * Handle the rename of a navigatio item.
     */
    private handleRename = (item: IKbNavigationItem, newName: string) => {
        switch (item.recordType) {
            case KbRecordType.CATEGORY:
                void this.props.categoryActions.patchCategory({ knowledgeCategoryID: item.recordID, name: newName });
                break;
            case KbRecordType.ARTICLE:
                void this.props.articleActions.patchArticle({ articleID: item.recordID, name: newName });
                break;
        }

        this.clearSelectedItem();
    };

    /**
     * Reset the selected item.
     */
    private clearSelectedItem = () => {
        const { selectedItem, treeData } = this.state;
        if (selectedItem === null) {
            return;
        }

        this.setState({
            selectedItem: null,
            writeMode: false,
        });
        this.updateAllItems({});
    };

    /// MODALS

    /**
     * Get the currently "selected" category. If we have an article selected use it's parent category.
     */
    private get currentTargetCategoryID(): number {
        const { selectedItem } = this.state;

        if (!selectedItem) {
            // This should be the category assosciated with the knowledge base once hooked up.
            return this.props.knowledgeBase.rootCategoryID;
        }

        if (selectedItem.data.recordType === KbRecordType.ARTICLE) {
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
            <NewCategoryForm
                isVisible={this.state.showNewCategoryModal}
                exitHandler={this.hideNewFolderModal}
                parentCategoryID={this.currentTargetCategoryID}
                buttonRef={this.newCategoryButtonRef}
            />
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
            <ModalConfirm
                isVisible={!!deleteItem}
                title={((<Translate source={'Delete "<0/>"'} c0={deleteItem?.data.name} />) as unknown) as string}
                onCancel={this.dismissDeleteModal}
                onConfirm={this.handleDeleteConfirm}
                elementToFocusOnExit={this.state.elementToFocusOnDeleteClose || document.body}
            >
                {deleteItem && (
                    <Translate
                        source={'Are you sure you want to delete <0/> "<1/>" ?'}
                        c0={this.getItemTypeLabel(deleteItem.data)}
                        c1={
                            <strong>
                                <em>{deleteItem.data.name}</em>
                            </strong>
                        }
                    />
                )}
            </ModalConfirm>
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
            this.setState({ selectedItem: null }); // Clear the selected item, because it is being deleted.

            switch (recordType) {
                case KbRecordType.ARTICLE:
                    await this.props.articleActions.patchStatus({ articleID: recordID, status: PublishStatus.DELETED });
                    break;
                case KbRecordType.CATEGORY:
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
            case KbRecordType.ARTICLE:
                return t("article");
            case KbRecordType.CATEGORY:
                return t("category");
            default:
                return recordType;
        }
    }

    /**
     * Disable editing of the whole tree. Takes an optional callback for when the state update has completed.
     */
    public getItemId = (id: string) => {
        return `${this.id}-${id}`;
    };

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

    private onDragUpdate = (update: IDragUpdate) => {
        const isHoveringArticle = update.combine && update.combine.draggableId.startsWith("article");
        const newAllowCombining = !isHoveringArticle;
        if (newAllowCombining !== this.state.allowCombining) {
            this.setState({ allowCombining: newAllowCombining });
        }
    };

    /**
     * Handle completion of drag.
     *
     * - Update item in local state, and additionally dispatch to the API endoint.
     */
    private onDragEnd = async (source: ITreeSourcePosition, destination?: ITreeDestinationPosition) => {
        const { treeData } = this.state;

        this.setState({
            dragging: false,
            disabled: false,
            writeMode: false,
        });
        if (!destination) {
            return;
        }

        // Do nothing if we leave it in the spot we started.
        if (source.index === destination.index && source.parentId === destination.parentId) {
            return;
        }

        const itemID = treeData.items[source.parentId].children[source.index];
        const item = treeData.items[itemID];

        const destinationItem = treeData.items[destination.parentId];
        if (destinationItem && destinationItem.data.recordType === KbRecordType.ARTICLE) {
            return;
        }

        if (destination.index === undefined) {
            if (destinationItem) {
                destination.index = destinationItem.children.length;
            } else {
                destination.index = 0;
            }
        }

        let newTree = moveItemOnTree(treeData, source, destination);
        const currentlySelectedItem = this.state.selectedItem;
        if (currentlySelectedItem) {
            // Touch the old item so it re-renders.
            newTree = mutateTree(newTree, currentlySelectedItem.id, {});
        }
        this.setState(
            {
                treeData: newTree,
            },
            () => {
                this.selectItem(item);
            },
        );
        this.storePatchArray(newTree);
        await this.props.navigationActions.patchNavigationFlat(this.props.knowledgeBase.knowledgeBaseID);
    };

    private onDragStart = (source: ITreeSourcePosition) => {
        this.setState({
            dragging: true,
        });
    };

    /**
     * Update all of the items in the tree with the same data partial.
     *
     * @param update
     */
    private updateAllItems(update: Partial<ITreeItem<INormalizedNavigationItem>>) {
        const data: ITreeData<INormalizedNavigationItem> = {
            rootId: this.rootNavigationItemID,
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
    private storePatchArray(data: ITreeData<INormalizedNavigationItem>) {
        const outOfTree = {};
        for (const [index, value] of Object.entries(data.items)) {
            outOfTree[index] = {
                ...value.data,
                children: value.children,
            };
        }
        const calcedData = NavigationModel.denormalizeData(outOfTree, this.rootNavigationItemID);
        this.props.navigationActions.setPatchItems(calcedData);
        return calcedData;
    }

    private static readonly DEFAULT_EXPAND_VALUE = true;

    /**
     * Convert the pure data representation of the tree data into one that contains UI state.
     *
     * - Makes ITreeData items.
     * - Preserves the existing IDs
     * - Preserves existing expand state if it exists.
     */
    private calcTree() {
        const { knowledgeBase } = this.props;
        const data: ITreeData<INormalizedNavigationItem> = {
            rootId: KbRecordType.CATEGORY + knowledgeBase.rootCategoryID,
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(this.props.navigationItems)) {
            if (!itemValue) {
                continue;
            }

            if (knowledgeBase.viewType !== KbViewType.GUIDE && itemValue.recordType === KbRecordType.ARTICLE) {
                continue;
            }

            let stateValue: ITreeItem<INormalizedNavigationItem> | null = null;
            if (this.state && this.state.treeData.items[itemID]) {
                stateValue = this.state.treeData.items[itemID];
            }

            const children = itemValue.children.filter(child => {
                if (knowledgeBase.viewType !== KbViewType.GUIDE && child.startsWith(KbRecordType.ARTICLE)) {
                    return false;
                }

                return true;
            });

            data.items[itemID] = {
                parentID: KbRecordType.CATEGORY + itemValue.parentID,
                id: itemID,
                hasChildren: children.length > 0,
                children,
                data: itemValue,
                isExpanded: stateValue ? stateValue.isExpanded : NavigationManager.DEFAULT_EXPAND_VALUE,
            };
        }

        return data;
    }

    /**
     * Keyboard handler for arrow up, arrow down, home, end and escape.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNavNode.tsx
     * @param event
     */
    private handleKeyDown = (e: React.KeyboardEvent) => {
        if (this.state.disabled || this.state.dragging) {
            return;
        }
        const currentItem = this.state.selectedItem;
        const container = this.self.current;
        let currentElement: HTMLElement | null = null;
        if (currentItem) {
            currentElement = document.getElementById(this.getItemId(currentItem.id));
        } else if (container) {
            let tabbable: HTMLElement = container.querySelector(".navigationManager-item[tabindex='0']") as HTMLElement;
            if (!tabbable) {
                tabbable = container.querySelector(".navigationManager-item") as HTMLElement;
            }
            if (tabbable) {
                tabbable.focus();
            }
        }

        if (!currentItem) {
            return;
        }

        const prevID = this.getPrevFlatID(currentItem);
        const nextID = this.getNextFlatID(currentItem);
        const parentID = this.calcParentID(currentItem);
        const firstID = this.getFirstItemID();
        const lastID = this.getLastItemID();
        const isFirstItem = currentItem.id === firstID;
        const isLastItem = currentItem.id === lastID;

        const shift = "-Shift";
        switch (`${e.key}${e.shiftKey ? shift : ""}`) {
            case "Escape":
                e.preventDefault();
                e.stopPropagation();
                this.setState({
                    disabled: false,
                    writeMode: false,
                });
                if (currentItem) {
                    this.selectItem(currentItem);
                }
                break;
            case "ArrowDown":
                if (!isLastItem && nextID) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.selectItemByID(nextID);
                }
                break;
            case "ArrowUp":
                if (!isFirstItem && prevID) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.selectItemByID(prevID);
                }
                break;
            case "ArrowRight":
                /**
                 * Only applies to items with children.
                 * When focus is on a closed node, opens the node; focus does not move.
                 * When focus is on a open node, moves focus to the first child node.
                 * When focus is on an end node, does nothing.
                 */
                if (currentItem && currentItem.children.length > 0) {
                    if (!currentItem.isExpanded) {
                        e.stopPropagation();
                        this.expandItem(currentItem.id);
                    } else {
                        const firstChildID = currentItem.children.length > 0 ? currentItem.children[0] : null;
                        if (firstChildID) {
                            this.selectItemByID(firstChildID);
                        }
                    }
                }
                break;
            case "ArrowLeft":
                /*
                    When focus is on an open node, closes the node.
                    When focus is on a child node that is also either an end node or a closed node, moves focus to its parent node.
                    When focus is on a root node that is also either an end node or a closed node, does nothing.
                */
                if (currentElement && currentItem) {
                    if (currentItem.children.length > 0 && currentItem.isExpanded) {
                        e.stopPropagation();
                        this.collapseItem(currentItem.id);
                    } else {
                        this.selectItemByID(parentID);
                    }
                }
                break;
            case "Home":
                if (!isFirstItem && firstID) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.selectItemByID(firstID);
                }
                break;
            case "End":
                if (!isLastItem && lastID) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.selectItemByID(lastID);
                }
                break;
        }
    };
}

interface IActions {
    navigationActions: NavigationActions;
    articleActions: ArticleActions;
    categoryActions: CategoryActions;
}
function mapStateToProps(state: IKnowledgeAppStoreState) {
    return state.knowledge.navigation;
}

function mapDispatchToProps(dispatch): IActions {
    return {
        articleActions: new ArticleActions(dispatch, apiv2),
        navigationActions: new NavigationActions(dispatch, apiv2),
        categoryActions: new CategoryActions(dispatch, apiv2),
    };
}
const Connected = connect(mapStateToProps, mapDispatchToProps)(NavigationManager);

export default Connected;
