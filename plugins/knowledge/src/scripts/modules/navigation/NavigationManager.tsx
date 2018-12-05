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
import { IKbNavigationItem } from "@knowledge/@types/api";

interface IProps {
    className?: string;
}

interface IState {
    treeData: ITreeData<IKbNavigationItem>;
    renameItemID: number | null;
    disabled: boolean;
}

export default class NavigationManager extends React.Component<IProps, IState> {
    public state: IState = {
        treeData: this.calcInitialTree([]),
        renameItemID: null,
        disabled: false,
    };

    public render() {
        return (
            <div className={classNames("navigationManager", this.props.className)}>
                <Tree
                    tree={this.state.treeData}
                    onDragEnd={this.onDragEnd}
                    onCollapse={this.collapseItem}
                    onExpand={this.expandItem}
                    renderItem={this.renderItem}
                    isDragEnabled={!this.state.disabled}
                    key={this.state.renameItemID ? this.state.renameItemID : undefined}
                />
            </div>
        );
    }

    private renderItem = (params: IRenderItemParams<INavigationItem>) => {
        const { provided, item, snapshot } = params;
        const hasChildren = item.children && item.children.length > 0;
        return (
            <NavigationManagerContent
                hasChildren={hasChildren}
                item={item as ITreeItem<IKbNavigationItem>}
                snapshot={snapshot}
                provided={provided}
                handleDelete={this.deleteSelectedItem}
                expandItem={this.expandItem}
                collapseItem={this.collapseItem}
                renameMode={this.editMode}
                stopRenameMode={this.stopEditMode}
                handleRename={this.handleRename}
                renameItemID={this.state.renameItemID}
                disableTree={this.disableTree}
                enableTree={this.enableTree}
            />
        );
    };

    private deleteSelectedItem = (item: ITreeItem<IKbNavigationItem>) => {
        alert("Delete Item: " + item.data!.recordID);
    };

    // For now, we hard code result. The edit can be accepted or rejected.
    private handleRename = (
        e: React.SyntheticEvent,
        item: ITreeItem<IKbNavigationItem>,
        newName: string,
        callback?: (result: any) => void,
    ) => {
        e.preventDefault();
        callback &&
            callback({
                result: true,
                message: "Success",
            });
        return true;
    };

    private editMode = (itemID: number | null) => {
        this.setState({
            renameItemID: itemID,
        });
    };

    private stopEditMode = () => {
        this.setState({
            renameItemID: null,
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
        this.setState({
            treeData: newTree,
        });
    };

    private calcInitialTree(items: IKbNavigationItem[]): ITreeData<IKbNavigationItem> {
        const data: ITreeData<IKbNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries({})) {
            const children = [];
            data.items[itemID] = {
                hasChildren: children.length > 0,
                id: itemID,
                children,
                data: itemValue as IKbNavigationItem,
                isExpanded: true,
            };
        }

        return data;
    }
}
