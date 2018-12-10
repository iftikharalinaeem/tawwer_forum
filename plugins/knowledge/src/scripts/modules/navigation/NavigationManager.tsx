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
import NavigationModel from "@knowledge/modules/navigation/NavigationModel";

interface IProps {
    className?: string;
    describedBy?: string;
}

interface IState {
    treeData: ITreeData<IKbNavigationItem>;
    selectedItem: ITreeItem<IKbNavigationItem> | null;
    selectedElement: HTMLElement | null;
    disabled: boolean;
    deleteMode: boolean;
    writeMode: boolean;
}

export default class NavigationManager extends React.Component<IProps, IState> {
    private self: React.RefObject<HTMLDivElement> = React.createRef();
    private foundFirst = false;
    private domElements = {};

    public state: IState = {
        treeData: this.calcInitialTree(),
        selectedItem: null,
        selectedElement: null,
        disabled: false,
        deleteMode: false,
        writeMode: false,
    };

    public render() {
        return (
            <div
                ref={this.self}
                className={classNames("navigationManager", this.props.className)}
                role="tree"
                aria-describedby={this.props.describedBy}
                onKeyDown={this.handleKeyDown}
            >
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
                writeMode={this.state.writeMode}
                deleteMode={this.state.deleteMode}
                firstID={this.getFirstTreeItemID()}
            />
        );
    };

    private getFirstTreeItemID = (): string | null => {
        const items = this.state.treeData.items;
        if (items) {
            return Object.values(items)[1].id;
        } else {
            return null;
        }
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
    };

    private selectItem = (
        selectedItem: ITreeItem<IKbNavigationItem>,
        writeMode: boolean = false,
        deleteMode: boolean = false,
        selectedElement: HTMLElement,
        callback?: () => void,
    ) => {
        this.setState(
            {
                disabled: writeMode || deleteMode,
                selectedItem,
                selectedElement,
                writeMode,
                deleteMode,
            },
            callback,
        );
    };

    private unSelectItem = () => {
        this.setState({
            selectedItem: null,
            selectedElement: null,
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
        this.setState({
            treeData: newTree,
            selectedItem: newTree.items[source.parentId].children[source.index],
        });
    };

    private calcInitialTree(): ITreeData<IKbNavigationItem> {
        const data: ITreeData<IKbNavigationItem> = {
            rootId: "knowledgeCategory1",
            items: {},
        };

        for (const [itemID, itemValue] of Object.entries(NavigationModel.normalizeData(this.dummyData))) {
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

    private get normalizedData() {
        const normalizedByID: { [id: string]: IKbNavigationItem } = {};
        for (const item of this.dummyData) {
            const id = item.recordType + item.recordID;
            normalizedByID[id] = item;
        }

        for (const [itemID, itemValue] of Object.entries(normalizedByID)) {
            if (itemValue.parentID > 0) {
                const lookupID = NavigationRecordType.KNOWLEDGE_CATEGORY + itemValue.parentID;
                const parentItem = normalizedByID[lookupID];
                if (!parentItem.children) {
                    parentItem.children = [];
                }
                parentItem.children.push(itemID);
            }
        }

        return normalizedByID;
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

    /**
     * Keyboard handler for arrow up, arrow down, home, end and escape.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNavNode.tsx
     * @param event
     */
    private handleKeyDown = (e: React.KeyboardEvent) => {
        console.log("this.state: ", this.state);
        const currentItem = this.state.selectedElement as HTMLElement;
        const tabHandler = new TabHandler(this.self.current! as HTMLElement);
        const shift = "-Shift";
        switch (`${e.key}${e.shiftKey ? shift : ""}`) {
            case "Escape":
                e.preventDefault();
                e.stopPropagation();
                this.setState({
                    disabled: false,
                    writeMode: false,
                });
            case "ArrowDown":
                e.preventDefault();
                e.stopPropagation();
                if (currentItem) {
                    currentItem.focus();
                }
                const nextElement = tabHandler.getNext(currentItem, false, false);
                if (nextElement) {
                    nextElement.focus();
                }
                break;
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
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Pee Mart",
                url: "http://dev.vanilla.localhost/kb/categories/2-pee-mart",
                parentID: 1,
                recordID: 2,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Predator Urine",
                url: "http://dev.vanilla.localhost/kb/categories/3-predator-urine",
                parentID: 2,
                recordID: 3,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Coyote Urine",
                url: "http://dev.vanilla.localhost/kb/categories/4-coyote-urine",
                parentID: 3,
                recordID: 4,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Fox Urine",
                url: "http://dev.vanilla.localhost/kb/categories/5-fox-urine",
                parentID: 3,
                recordID: 5,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Bobcat Urine",
                url: "http://dev.vanilla.localhost/kb/categories/6-bobcat-urine",
                parentID: 3,
                recordID: 6,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "P-Gel",
                url: "http://dev.vanilla.localhost/kb/categories/7-p-gel",
                parentID: 2,
                recordID: 7,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "P-Cover Granules",
                url: "http://dev.vanilla.localhost/kb/categories/8-p-cover-granules",
                parentID: 2,
                recordID: 8,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Prey Animals",
                url: "http://dev.vanilla.localhost/kb/categories/9-prey-animals",
                parentID: 2,
                recordID: 9,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Armadillos",
                url: "http://dev.vanilla.localhost/kb/categories/10-armadillos",
                parentID: 9,
                recordID: 10,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Chipmunks",
                url: "http://dev.vanilla.localhost/kb/categories/11-chipmunks",
                parentID: 9,
                recordID: 11,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Dispensers",
                url: "http://dev.vanilla.localhost/kb/categories/12-dispensers",
                parentID: 2,
                recordID: 12,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Mountain Lion",
                url: "http://dev.vanilla.localhost/kb/categories/13-mountain-lion",
                parentID: 8,
                recordID: 13,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Bear",
                url: "http://dev.vanilla.localhost/kb/categories/14-bear",
                parentID: 8,
                recordID: 14,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Wolf",
                url: "http://dev.vanilla.localhost/kb/categories/15-wolf",
                parentID: 8,
                recordID: 15,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "P-Wicks",
                url: "http://dev.vanilla.localhost/kb/categories/16-p-wicks",
                parentID: 12,
                recordID: 16,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "P-Dispensers",
                url: "http://dev.vanilla.localhost/kb/categories/17-p-dispensers",
                parentID: 12,
                recordID: 17,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Test Folder!!!",
                url: "http://dev.vanilla.localhost/kb/categories/18-test-folder",
                parentID: 3,
                recordID: 18,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Category in Base 1",
                url: "http://dev.vanilla.localhost/kb/categories/19-category-in-base-1",
                parentID: 1,
                recordID: 19,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Test",
                url: "http://dev.vanilla.localhost/kb/categories/20-test",
                parentID: 2,
                recordID: 20,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "asdf",
                url: "http://dev.vanilla.localhost/kb/categories/21-asdf",
                parentID: 2,
                recordID: 21,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "Search Category 1",
                url: "http://dev.vanilla.localhost/kb/categories/22-search-category-1",
                parentID: 1,
                recordID: 22,
                sort: null,
                recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            },
            {
                name: "What about PHP version??",
                url: "http://dev.vanilla.localhost/kb/articles/1-what-about-php-version",
                recordID: 1,
                sort: 0,
                parentID: 12,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Article 2",
                url: "http://dev.vanilla.localhost/kb/articles/2-article-2",
                recordID: 2,
                sort: 0,
                parentID: 7,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test 3",
                url: "http://dev.vanilla.localhost/kb/articles/3-test-3",
                recordID: 3,
                sort: 0,
                parentID: 3,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Revised!!! Test Rev Article",
                url: "http://dev.vanilla.localhost/kb/articles/278-revised-test-rev-article",
                recordID: 278,
                sort: null,
                parentID: 1,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test article",
                url: "http://dev.vanilla.localhost/kb/articles/280-test-article",
                recordID: 280,
                sort: null,
                parentID: 2,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test headings",
                url: "http://dev.vanilla.localhost/kb/articles/281-test-headings",
                recordID: 281,
                sort: null,
                parentID: 1,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test",
                url: "http://dev.vanilla.localhost/kb/articles/290-test",
                recordID: 290,
                sort: null,
                parentID: 1,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "test in pee mart",
                url: "http://dev.vanilla.localhost/kb/articles/291-test-in-pee-mart",
                recordID: 291,
                sort: null,
                parentID: 2,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test heading article",
                url: "http://dev.vanilla.localhost/kb/articles/293-test-heading-article",
                recordID: 293,
                sort: null,
                parentID: 18,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "test 2",
                url: "http://dev.vanilla.localhost/kb/articles/302-test-2",
                recordID: 302,
                sort: null,
                parentID: 19,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "test",
                url: "http://dev.vanilla.localhost/kb/articles/309-test",
                recordID: 309,
                sort: null,
                parentID: 1,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "asdfasdfasdfasfasdf",
                url: "http://dev.vanilla.localhost/kb/articles/315-asdfasdfasdfasfasdf",
                recordID: 315,
                sort: null,
                parentID: 19,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test Draft Article",
                url: "http://dev.vanilla.localhost/kb/articles/316-test-draft-article",
                recordID: 316,
                sort: null,
                parentID: 19,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "What about PHP version??",
                url: "http://dev.vanilla.localhost/kb/articles/317-what-about-php-version",
                recordID: 317,
                sort: null,
                parentID: 12,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Search Article number 1",
                url: "http://dev.vanilla.localhost/kb/articles/319-search-article-number-1",
                recordID: 319,
                sort: null,
                parentID: 22,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test search article number 2",
                url: "http://dev.vanilla.localhost/kb/articles/320-test-search-article-number-2",
                recordID: 320,
                sort: null,
                parentID: 22,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "Test search article 3",
                url: "http://dev.vanilla.localhost/kb/articles/321-test-search-article-3",
                recordID: 321,
                sort: null,
                parentID: 22,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "A new article title",
                url: "http://dev.vanilla.localhost/kb/articles/322-a-new-article-title",
                recordID: 322,
                sort: null,
                parentID: 22,
                recordType: NavigationRecordType.ARTICLE,
            },
            {
                name: "test",
                url: "http://dev.vanilla.localhost/kb/articles/323-test",
                recordID: 323,
                sort: null,
                parentID: 1,
                recordType: NavigationRecordType.ARTICLE,
            },
        ];
    }
}
