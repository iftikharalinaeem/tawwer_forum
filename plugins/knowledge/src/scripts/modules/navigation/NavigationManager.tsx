/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Tree, {
    ITreeData,
    mutateTree,
    ITreeSourcePosition,
    ITreeDestinationPosition,
    moveItemOnTree,
    IRenderItemParams,
    ITreeItem,
} from "@atlaskit/tree";
import NavigationManagerContent from "@knowledge/modules/navigation/NavigationManagerContent";
import classNames from "classnames";
import { INavigationItem } from "@library/@types/api";
import { IKbNavigationItem, NavigationRecordType } from "@knowledge/@types/api";
import NavigationModel, {
    INormalizedNavigationItems,
    INavigationStoreState,
} from "@knowledge/modules/navigation/NavigationModel";
import { connect } from "react-redux";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";

interface IProps extends IActions, INavigationStoreState {
    className?: string;
    navigationItems: INormalizedNavigationItems;
    knowledgeBaseID: number;
}

interface IState {
    treeData: ITreeData<IKbNavigationItem>;
    selectedItem: ITreeItem<IKbNavigationItem> | null;
    disabled: boolean;
    deleteMode: boolean;
    writeMode: boolean;
}

export class NavigationManager extends React.Component<IProps, IState> {
    private self: React.RefObject<HTMLDivElement> = React.createRef();

    public state: IState = {
        treeData: this.calcTree(),
        selectedItem: null,
        disabled: false,
        deleteMode: false,
        writeMode: false,
    };

    public render() {
        return (
            <div ref={this.self} className={classNames("navigationManager", this.props.className)}>
                <Tree
                    tree={this.state.treeData}
                    onDragEnd={this.onDragEnd}
                    onCollapse={this.collapseItem}
                    onExpand={this.expandItem}
                    renderItem={this.renderItem}
                    isDragEnabled={!this.state.disabled}
                    key={`${this.state.selectedItem ? this.state.selectedItem.id : undefined}-${this.state.writeMode}-${
                        this.state.deleteMode
                    }`}
                />
            </div>
        );
    }

    private renderItem = (params: IRenderItemParams<INavigationItem>) => {
        const { provided, item, snapshot } = params;
        const hasChildren = item.children && item.children.length > 0;
        return (
            <NavigationManagerContent
                item={item}
                snapshot={snapshot}
                provided={provided}
                hasChildren={hasChildren}
                onRenameSubmit={this.handleRename}
                onDelete={this.handleDelete}
                handleDelete={this.handleDelete}
                expandItem={this.expandItem}
                collapseItem={this.collapseItem}
                selectedItem={this.state.selectedItem}
                selectItem={this.selectItem}
                unSelectItem={this.unSelectItem}
                disableTree={this.disableTree}
                enableTree={this.enableTree}
                writeMode={this.state.writeMode}
                deleteMode={this.state.deleteMode}
            />
        );
    };

    public async componentDidMount() {
        const { knowledgeBaseID } = this.props;
        this.props.navigationActions.getNavigationFlat({ knowledgeBaseID });
    }

    public componentDidUpdate(prevProps: IProps) {
        if (this.props.navigationItems !== prevProps.navigationItems) {
            this.setState({ treeData: this.calcTree() });
        }
    }

    /**
     * Collapse all items in the tree.
     */
    public collapseAll() {
        this.updateAllItems({ isExpanded: false });
    }

    /**
     * Expand all items in the tree.
     */
    public expandAll() {
        this.updateAllItems({ isExpanded: true });
    }

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
        if (item.recordType === NavigationRecordType.KNOWLEDGE_CATEGORY) {
            void this.props.categoryActions.patchCategory({ knowledgeCategoryID: item.recordID, name: newName });
        }

        this.unSelectItem();
    };

    /**
     * Select a single item. Takes an optional callback for after the state has been updated.
     */
    private selectItem = (
        selectedItem: ITreeItem<IKbNavigationItem>,
        writeMode: boolean = false,
        deleteMode: boolean = false,
        callback?: () => void,
    ) => {
        this.setState(
            {
                selectedItem,
                writeMode,
                deleteMode,
            },
            callback,
        );
    };

    private unSelectItem = () => {
        this.setState({
            selectedItem: null,
        });
    };

    /**
     * Disable editing of the whole tree. Takes an optional callback for when the state update has completed.
     */
    private disableTree = (callback?: () => void) => {
        this.setState(
            {
                disabled: true,
            },
            callback,
        );
    };

    /**
     * Enable editing of the whole tree. Takes an optional callback for when the state update has completed.
     */
    private enableTree = (callback?: () => void) => {
        this.setState(
            {
                disabled: false,
            },
            callback,
        );
    };

    private onDragEnd = (source: ITreeSourcePosition, destination?: ITreeDestinationPosition) => {
        const { treeData } = this.state;

        if (!destination) {
            return;
        }
        const newTree = moveItemOnTree(treeData, source, destination);
        this.setState(
            {
                treeData: newTree,
                selectedItem: newTree.items[source.parentId].children[source.index],
            },
            () => {
                void this.props.navigationActions.patchNavigationFlat(this.calcPatchArray(this.state.treeData));
            },
        );
    };

    private handleDelete = () => {
        alert("Do Delete");
    };

    /**
     * Update all of the items in the tree with the same data partial.
     *
     * @param update
     */
    private updateAllItems(update: Partial<ITreeItem<IKbNavigationItem>>) {
        const data: ITreeData<IKbNavigationItem> = {
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
    private calcPatchArray(data: ITreeData<IKbNavigationItem>) {
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
        const data: ITreeData<IKbNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(this.props.navigationItems)) {
            let stateValue: ITreeItem<IKbNavigationItem> | null = null;
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
    null,
    { withRef: true },
)(NavigationManager);

export default Connected;
