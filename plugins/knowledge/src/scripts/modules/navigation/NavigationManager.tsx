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
} from "@atlaskit/tree";
import classNames from "classNames";
import { t } from "@library/application";
import NavigationManagerContent from "@knowledge/modules/navigation/NavigationManagerContent";

interface IProps {}

interface IState {
    treeData: ITreeData<IKbNavigationItem>;
}

export default class NavigationManager extends React.Component<IProps, IState> {
    public state: IState = {
        treeData: this.calcInitialTree(this.dummyData),
    };

    public render() {
        return (
            <>
                <div className="tree">
                    <Tree
                        tree={this.state.treeData}
                        onDragEnd={this.onDragEnd}
                        onCollapse={this.collapseItem}
                        onExpand={this.expandItem}
                        renderItem={this.renderItem}
                        isDragEnabled={true}
                    />
                </div>
            </>
        );
    }

    private renderItem = (params: IRenderItemParams<IKbNavigationItem>) => {
        const { provided, item, snapshot } = params;
        const hasChildren = item.children && item.children.length > 0;
        return (
            <div
                className={classNames("tree-item", { isDragging: snapshot.isDragging })}
                ref={provided.innerRef}
                {...provided.draggableProps}
                {...provided.dragHandleProps}
                aria-roledescription={t(provided.dragHandleProps!["aria-roledescription"])}
            >
                <NavigationManagerContent
                    handleEdit={this.handleEdit}
                    hasChildren={hasChildren}
                    item={item}
                    handleDelete={this.deleteSelectedItem}
                    expandItem={this.expandItem}
                    collapseItem={this.collapseItem}
                />
            </div>
        );
    };

    private deleteSelectedItem = (item: IKbNavigationItem) => {
        alert("Delete Item: " + item!.recordID);
    };

    // For now, we hard code result. The edit can be accepted or rejected.
    private handleEdit = (item: IKbNavigationItem) => {
        return true;
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
