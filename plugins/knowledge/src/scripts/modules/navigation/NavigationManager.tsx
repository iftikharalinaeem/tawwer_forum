/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IKbNavigationItem } from "@knowledge/@types/api";
import Tree, {
    ITreeData,
    mutateTree,
    ITreeSourcePosition,
    ITreeDestinationPosition,
    moveItemOnTree,
    IRenderItemParams,
    ITreeItem,
} from "@atlaskit/tree";
import { t } from "@library/application";
import NavigationManagerContent from "@knowledge/modules/navigation/NavigationManagerContent";
import classNames from "classnames";
import TabHandler from "@library/TabHandler";

interface IProps {
    className?: string;
}

interface IState {
    treeData: ITreeData<IKbNavigationItem>;
    selectedItem: ITreeItem<IKbNavigationItem> | null;
    disabled: boolean;
    writeMode: boolean;
}

export default class NavigationManager extends React.Component<IProps, IState> {
    private self: React.RefObject<HTMLDivElement> = React.createRef();

    public state: IState = {
        treeData: this.calcInitialTree(this.dummyData),
        selectedItem: null,
        disabled: false,
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
                    key={`${this.state.selectedItem ? this.state.selectedItem.id : undefined}-${this.state.writeMode}`}
                />
            </div>
        );
    }

    private renderItem = (params: IRenderItemParams<IKbNavigationItem>) => {
        const { provided, item, snapshot } = params;
        const data = item.data!;
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
                type={this.getType(data.recordType)}
                handleKeyDown={this.handleKeyDown}
                writeMode={this.state.writeMode}
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

    private selectItem = (selectedItem: ITreeItem<IKbNavigationItem>, writeMode: boolean) => {
        this.setState(
            {
                selectedItem,
                writeMode,
            },
            () => {
                console.log("this.state: ", this.state);
            },
        );
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
        this.setState({
            treeData: newTree,
            selectedItem: newTree.items[source.parentId].children[source.index],
        });
    };

    private calcInitialTree(items: IKbNavigationItem[]): ITreeData<IKbNavigationItem> {
        const data: ITreeData<IKbNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(this.normalizedData)) {
            const children = itemValue.children || [];
            data.items[itemID] = {
                hasChildren: children.length > 0,
                id: itemID,
                children,
                data: itemValue,
                isExpanded: true,
            };
        }

        return data;
    }

    private handleDelete = () => {
        alert("Do Delete");
    };

    private get normalizedData() {
        const normalizedByID: { [id: string]: IKbNavigationItem } = {};
        for (const item of this.dummyData) {
            const id = item.recordType + item.recordID;
            normalizedByID[id] = item;
        }

        for (const [itemID, itemValue] of Object.entries(normalizedByID)) {
            if (itemValue.parentID > 0) {
                const lookupID = "knowledgeCategory" + itemValue.parentID;
                const parentItem = normalizedByID[lookupID];
                if (!parentItem.children) {
                    parentItem.children = [];
                }
                parentItem.children.push(itemID);
            }
        }

        return normalizedByID;
    }

    private getSelectedItemId = (): string | null => {
        return this.state.selectedItem ? this.state.selectedItem.id : null;
    };

    private getType = (type: string) => {
        switch (type) {
            case "article":
                return t("article");
            case "knowledgeCategory":
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
    private handleKeyDown = (e: React.KeyboardEvent) => {
        const currentItem = e.currentTarget.firstChild as HTMLElement;

        const tabHandler = new TabHandler(this.self.current!);
        const shift = "-Shift";

        switch (
            `${e.key}${e.shiftKey ? shift : ""}` // See SiteNavNode for the rest of the keyboard handler
            // case "Tab":
            //     e.stopPropagation();
            //     e.preventDefault();
            //     const nextElement = tabHandler.getNext(currentItem, false, true);
            //     if (nextElement) {
            //         nextElement.focus();
            //     }
            //     break;
            // case "Tab" + shift:
            //     e.stopPropagation();
            //     e.preventDefault();
            //     const prevElement = tabHandler.getNext(currentItem, true, true);
            //     if (prevElement) {
            //         prevElement.focus();
            //     }
            //     break;
            // case "ArrowDown":
            //     /*
            //         Moves focus one row or one cell down, depending on whether a row or cell is currently focused.
            //         If focus is on the bottom row, focus does not move.
            //      */
            //     e.preventDefault();
            //     e.stopPropagation();
            //     if (currentItem) {
            //         const nextElement = tabHandler.getNext(currentItem, false, false);
            //         if (nextElement) {
            //             nextElement.focus();
            //         }
            //     }
            //     break;
            // case "ArrowUp":
            //     /*
            //         Moves focus one row or one cell up, depending on whether a row or cell is currently focused.
            //         If focus is on the top row, focus does not move.
            //      */
            //     if (currentItem) {
            //         e.preventDefault();
            //         e.stopPropagation();
            //         const prevElement = tabHandler.getNext(currentItem, true, false);
            //         if (prevElement) {
            //             prevElement.focus();
            //         }
            //     }
            //     break;
            // case "Home":
            //     /*
            //         If a cell is focused, moves focus to the previous interactive widget in the current row.
            //         If a row is focused, moves focus out of the treegrid.
            //      */
            //     e.preventDefault();
            //     e.stopPropagation();
            //     const firstLink = tabHandler.getInitial();
            //     if (firstLink) {
            //         firstLink.focus();
            //     }
            //     break;
            // case "End":
            //     /*
            //         If a row is focused, moves to the first row.
            //         If a cell is focused, moves focus to the first cell in the row containing focus.
            //      */
            //     e.preventDefault();
            //     e.stopPropagation();
            //     const lastLink = tabHandler.getLast();
            //     if (lastLink) {
            //         lastLink.focus();
            //     }
            //     break;
        ) {
        }
    };

    private get dummyData(): IKbNavigationItem[] {
        return [
            {
                name: "Base 1",
                url: "http://dev.vanilla.localhost/kb/categories/1-base-1",
                parentID: -1,
                recordID: 1,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Pee Mart",
                url: "http://dev.vanilla.localhost/kb/categories/2-pee-mart",
                parentID: 1,
                recordID: 2,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Predator Urine",
                url: "http://dev.vanilla.localhost/kb/categories/3-predator-urine",
                parentID: 2,
                recordID: 3,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Coyote Urine",
                url: "http://dev.vanilla.localhost/kb/categories/4-coyote-urine",
                parentID: 3,
                recordID: 4,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Fox Urine",
                url: "http://dev.vanilla.localhost/kb/categories/5-fox-urine",
                parentID: 3,
                recordID: 5,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Bobcat Urine",
                url: "http://dev.vanilla.localhost/kb/categories/6-bobcat-urine",
                parentID: 3,
                recordID: 6,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "P-Gel",
                url: "http://dev.vanilla.localhost/kb/categories/7-p-gel",
                parentID: 2,
                recordID: 7,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "P-Cover Granules",
                url: "http://dev.vanilla.localhost/kb/categories/8-p-cover-granules",
                parentID: 2,
                recordID: 8,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Prey Animals",
                url: "http://dev.vanilla.localhost/kb/categories/9-prey-animals",
                parentID: 2,
                recordID: 9,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Armadillos",
                url: "http://dev.vanilla.localhost/kb/categories/10-armadillos",
                parentID: 9,
                recordID: 10,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Chipmunks",
                url: "http://dev.vanilla.localhost/kb/categories/11-chipmunks",
                parentID: 9,
                recordID: 11,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Dispensers",
                url: "http://dev.vanilla.localhost/kb/categories/12-dispensers",
                parentID: 2,
                recordID: 12,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Mountain Lion",
                url: "http://dev.vanilla.localhost/kb/categories/13-mountain-lion",
                parentID: 8,
                recordID: 13,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Bear",
                url: "http://dev.vanilla.localhost/kb/categories/14-bear",
                parentID: 8,
                recordID: 14,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Wolf",
                url: "http://dev.vanilla.localhost/kb/categories/15-wolf",
                parentID: 8,
                recordID: 15,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "P-Wicks",
                url: "http://dev.vanilla.localhost/kb/categories/16-p-wicks",
                parentID: 12,
                recordID: 16,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "P-Dispensers",
                url: "http://dev.vanilla.localhost/kb/categories/17-p-dispensers",
                parentID: 12,
                recordID: 17,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Test Folder!!!",
                url: "http://dev.vanilla.localhost/kb/categories/18-test-folder",
                parentID: 3,
                recordID: 18,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Category in Base 1",
                url: "http://dev.vanilla.localhost/kb/categories/19-category-in-base-1",
                parentID: 1,
                recordID: 19,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Test",
                url: "http://dev.vanilla.localhost/kb/categories/20-test",
                parentID: 2,
                recordID: 20,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "asdf",
                url: "http://dev.vanilla.localhost/kb/categories/21-asdf",
                parentID: 2,
                recordID: 21,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "Search Category 1",
                url: "http://dev.vanilla.localhost/kb/categories/22-search-category-1",
                parentID: 1,
                recordID: 22,
                sort: null,
                recordType: "knowledgeCategory",
            },
            {
                name: "What about PHP version??",
                url: "http://dev.vanilla.localhost/kb/articles/1-what-about-php-version",
                recordID: 1,
                sort: 0,
                parentID: 12,
                recordType: "article",
            },
            {
                name: "Article 2",
                url: "http://dev.vanilla.localhost/kb/articles/2-article-2",
                recordID: 2,
                sort: 0,
                parentID: 7,
                recordType: "article",
            },
            {
                name: "Test 3",
                url: "http://dev.vanilla.localhost/kb/articles/3-test-3",
                recordID: 3,
                sort: 0,
                parentID: 3,
                recordType: "article",
            },
            {
                name: "Revised!!! Test Rev Article",
                url: "http://dev.vanilla.localhost/kb/articles/278-revised-test-rev-article",
                recordID: 278,
                sort: null,
                parentID: 1,
                recordType: "article",
            },
            {
                name: "Test article",
                url: "http://dev.vanilla.localhost/kb/articles/280-test-article",
                recordID: 280,
                sort: null,
                parentID: 2,
                recordType: "article",
            },
            {
                name: "Test headings",
                url: "http://dev.vanilla.localhost/kb/articles/281-test-headings",
                recordID: 281,
                sort: null,
                parentID: 1,
                recordType: "article",
            },
            {
                name: "Test",
                url: "http://dev.vanilla.localhost/kb/articles/290-test",
                recordID: 290,
                sort: null,
                parentID: 1,
                recordType: "article",
            },
            {
                name: "test in pee mart",
                url: "http://dev.vanilla.localhost/kb/articles/291-test-in-pee-mart",
                recordID: 291,
                sort: null,
                parentID: 2,
                recordType: "article",
            },
            {
                name: "Test heading article",
                url: "http://dev.vanilla.localhost/kb/articles/293-test-heading-article",
                recordID: 293,
                sort: null,
                parentID: 18,
                recordType: "article",
            },
            {
                name: "test 2",
                url: "http://dev.vanilla.localhost/kb/articles/302-test-2",
                recordID: 302,
                sort: null,
                parentID: 19,
                recordType: "article",
            },
            {
                name: "test",
                url: "http://dev.vanilla.localhost/kb/articles/309-test",
                recordID: 309,
                sort: null,
                parentID: 1,
                recordType: "article",
            },
            {
                name: "asdfasdfasdfasfasdf",
                url: "http://dev.vanilla.localhost/kb/articles/315-asdfasdfasdfasfasdf",
                recordID: 315,
                sort: null,
                parentID: 19,
                recordType: "article",
            },
            {
                name: "Test Draft Article",
                url: "http://dev.vanilla.localhost/kb/articles/316-test-draft-article",
                recordID: 316,
                sort: null,
                parentID: 19,
                recordType: "article",
            },
            {
                name: "What about PHP version??",
                url: "http://dev.vanilla.localhost/kb/articles/317-what-about-php-version",
                recordID: 317,
                sort: null,
                parentID: 12,
                recordType: "article",
            },
            {
                name: "Search Article number 1",
                url: "http://dev.vanilla.localhost/kb/articles/319-search-article-number-1",
                recordID: 319,
                sort: null,
                parentID: 22,
                recordType: "article",
            },
            {
                name: "Test search article number 2",
                url: "http://dev.vanilla.localhost/kb/articles/320-test-search-article-number-2",
                recordID: 320,
                sort: null,
                parentID: 22,
                recordType: "article",
            },
            {
                name: "Test search article 3",
                url: "http://dev.vanilla.localhost/kb/articles/321-test-search-article-3",
                recordID: 321,
                sort: null,
                parentID: 22,
                recordType: "article",
            },
            {
                name: "A new article title",
                url: "http://dev.vanilla.localhost/kb/articles/322-a-new-article-title",
                recordID: 322,
                sort: null,
                parentID: 22,
                recordType: "article",
            },
            {
                name: "test",
                url: "http://dev.vanilla.localhost/kb/articles/323-test",
                recordID: 323,
                sort: null,
                parentID: 1,
                recordType: "article",
            },
        ];
    }
}
