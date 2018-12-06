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
import { IKbNavigationItem, NavigationRecordType, IPatchFlatItem } from "@knowledge/@types/api";
import TabHandler from "@library/TabHandler";
import { t } from "@library/application";
import NavigationModel, { INormalizedNavigationItems } from "@knowledge/modules/navigation/NavigationModel";

interface IProps {
    className?: string;
    navigationItems: INormalizedNavigationItems;
    updateItems: (newItems: IPatchFlatItem[]) => void;
}

interface IState {
    treeData: ITreeData<IKbNavigationItem>;
    selectedItem: ITreeItem<IKbNavigationItem> | null;
    disabled: boolean;
    deleteMode: boolean;
    writeMode: boolean;
}

export default class NavigationManager extends React.Component<IProps, IState> {
    private self: React.RefObject<HTMLDivElement> = React.createRef();

    public state: IState = {
        treeData: this.calcInitialTree(),
        selectedItem: null,
        disabled: false,
        deleteMode: false,
        writeMode: false,
    };

    public render() {
        const data = JSON.parse(JSON.stringify(this.state.treeData));
        return (
            <div ref={this.self} className={classNames("navigationManager", this.props.className)}>
                <Tree
                    tree={this.state.treeData}
                    onDragEnd={this.onDragEnd}
                    onCollapse={this.collapseItem}
                    onExpand={this.expandItem}
                    renderItem={this.renderItem}
                    isDragEnabled={!this.state.disabled}
                    key={this.state.selectedItem ? this.state.selectedItem.id : undefined}
                />
            </div>
        );
    }

    private renderItem = (params: IRenderItemParams<INavigationItem>) => {
        const { provided, item, snapshot } = params;
        const data = item.data!;
        const hasChildren = item.children && item.children.length > 0;
        const isCurrent = this.getSelectedItemId() === item.id;
        const isWriteMode = this.state.writeMode && isCurrent;
        const isDeleteMode = this.state.deleteMode && isCurrent;

        return (
            <NavigationManagerContent
                item={item as ITreeItem<IKbNavigationItem>}
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
                type={this.getType(data.recordType)}
                key={`${item.id}-${data.name}-${isWriteMode}-${isDeleteMode}-${isCurrent}`}
                current={isCurrent}
                writeMode={isWriteMode}
                deleteMode={isDeleteMode}
                handleKeyDown={this.handleKeyDown}
            />
        );
    };

    private deleteSelectedItem = (item: ITreeItem<IKbNavigationItem>) => {
        alert("Delete Item: " + item.data!.recordID);
    };

    // For now, we hard code result. The edit can be accepted or rejected.
    private handleRename = () => {
        const result = {
            result: true,
            message: "Success",
        };
        this.unSelectItem();
    };

    private selectItem = (
        selectedItem: ITreeItem<IKbNavigationItem>,
        writeMode: boolean = false,
        deleteMode: boolean = false,
    ) => {
        this.setState({
            selectedItem,
            writeMode,
            deleteMode,
        });
    };

    private unSelectItem = () => {
        this.setState({
            selectedItem: null,
        });
    };

    private disableTree = () => {
        this.setState({
            disabled: true,
        });
    };

    private enableTree = () => {
        this.setState({
            disabled: false,
        });
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
            },
            () => {
                this.props.updateItems(this.calcPatchArray(this.state.treeData));
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

    private calcInitialTree(): ITreeData<IKbNavigationItem> {
        const data: ITreeData<IKbNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(this.props.navigationItems)) {
            data.items[itemID] = {
                hasChildren: itemValue.children.length > 0,
                id: itemID,
                children: itemValue.children,
                data: itemValue as IKbNavigationItem,
                isExpanded: true,
            };
        }

        return data;
    }

    private handleDelete = () => {
        alert("Do Delete");
    };

    private getSelectedItemId = (): string | null => {
        return this.state.selectedItem ? this.state.selectedItem.id : null;
    };

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

    /**
     * Keyboard handler for arrow up, arrow down, home and end.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNavNode.tsx
     * @param event
     */
    private handleKeyDown = (event: React.KeyboardEvent) => {
        if (document.activeElement === null) {
            return;
        }
        const currentItem = document.activeElement;
        // const selectedNode = currentLink.closest(".siteNavNode");
        // const siteNavRoot = currentLink.closest(".siteNav");
        const tabHandler = new TabHandler(this.self.current!);
        const shift = "-Shift";

        switch (
            `${event.key}${event.shiftKey ? shift : ""}` // See SiteNavNode for the rest of the keyboard handler
        ) {
            case "Tab":
                const nextElement = tabHandler.getNext(currentItem, false, true);
                if (nextElement) {
                    nextElement.focus();
                }
                break;
            case "Tab" + shift:
                const prevElement = tabHandler.getNext(currentItem, true, true);
                if (prevElement) {
                    prevElement.focus();
                }
                break;
        }
    };
}
