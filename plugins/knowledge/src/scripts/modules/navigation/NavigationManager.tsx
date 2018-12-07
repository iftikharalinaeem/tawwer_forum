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
import TabHandler from "@library/TabHandler";
import { t } from "@library/application";
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
        const data = item.data!;
        const hasChildren = item.children && item.children.length > 0;
        return (
            <NavigationManagerContent
                item={item}
                snapshot={snapshot}
                provided={provided}
                hasChildren={hasChildren}
                onRenameSubmit={this.commitRename}
                onDelete={this.handleDelete}
                handleDelete={this.handleDelete}
                expandItem={this.expandItem}
                collapseItem={this.collapseItem}
                selectedItem={this.state.selectedItem}
                selectItem={this.selectItem}
                unSelectItem={this.unSelectItem}
                disableTree={this.disableTree}
                enableTree={this.enableTree}
                type={this.getType(data.recordType)}
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

    private deleteSelectedItem = (item: ITreeItem<IKbNavigationItem>) => {
        alert("Delete Item: " + item.data!.recordID);
    };

    // For now, we hard code result. The edit can be accepted or rejected.
    private commitRename = (item: IKbNavigationItem, newName: string) => {
        if (item.recordType === NavigationRecordType.KNOWLEDGE_CATEGORY) {
            void this.props.categoryActions.patchCategory({ knowledgeCategoryID: item.recordID, name: newName });
        }

        this.unSelectItem();
    };

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

    private disableTree = (callback?: () => void) => {
        this.setState(
            {
                disabled: true,
            },
            callback,
        );
    };

    private enableTree = (callback?: () => void) => {
        this.setState(
            {
                disabled: false,
            },
            callback,
        );
    };

    private expandItem = (itemId: string) => {
        const { treeData } = this.state;
        this.setState({
            treeData: mutateTree(treeData, itemId, { isExpanded: true }),
        });
    };

    private collapseItem = (itemId: string) => {
        const { treeData } = this.state;
        this.setState({
            treeData: mutateTree(treeData, itemId, { isExpanded: false }),
        });
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

    private updateAllItems(update: Partial<ITreeItem<IKbNavigationItem>>) {
        const data: ITreeData<IKbNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(this.state.treeData.items)) {
            const newData = update.data! || {};
            data.items[itemID] = {
                ...itemValue,
                ...update,
                data: {
                    ...itemValue.data!,
                    ...newData,
                },
            };
        }

        this.setState({ treeData: data });
    }

    public collapseAll() {
        this.updateAllItems({ isExpanded: false });
    }

    public expandAll() {
        this.updateAllItems({ isExpanded: true });
    }

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

    private getType = (type: string) => {
        switch (type) {
            case "article":
                return t("article");
            case NavigationRecordType.KNOWLEDGE_CATEGORY:
                return t("category");
            default:
                return type;
        }
    };

    private handleDelete = () => {
        alert("Do Delete");
    };
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
