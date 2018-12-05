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
    newName: string | null;
    disabled: boolean;
}

export default class NavigationManager extends React.Component<IProps, IState> {
    public state: IState = {
        treeData: this.calcInitialTree([]),
        renameItemID: null,
        disabled: false,
        newName: null,
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
                activateRenameMode={this.activateRenameMode}
                disableRenameMode={this.disableRenameMode}
                handleRename={this.handleRename}
                renameItemID={this.state.renameItemID}
                disableTree={this.disableTree}
                enableTree={this.enableTree}
                handleOnChange={this.handleOnChange}
            />
        );
    };

    private deleteSelectedItem = (item: ITreeItem<IKbNavigationItem>) => {
        alert("Delete Item: " + item.data!.recordID);
    };

    private handleOnChange = (e: React.SyntheticEvent) => {
        e.preventDefault();
        if (e.currentTarget.value) {
            this.setState(
                {
                    newName: e.value,
                },
                () => {
                    return {
                        result: true,
                        message: "Success",
                    };
                },
            );
        }
    };

    // For now, we hard code result. The edit can be accepted or rejected.
    private handleRename = (e: React.SyntheticEvent, callback) => {
        const result = {
            result: true,
            message: "Success",
        };
        callback(result);
    };

    private activateRenameMode = (itemID: number, callback?: () => void) => {
        this.setState(
            {
                renameItemID: itemID,
                newName: this.state.newName,
            },
            callback,
        );
    };

    private handleNameChange = e => {
        e.preventDefault();
        e.stopPropagation();
        this.setState({
            newName: e.target.value,
        });
    };

    private disableRenameMode = (callback?: () => void) => {
        this.setState(
            {
                renameItemID: null,
                newName: null,
            },
            callback,
        );
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
