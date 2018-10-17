/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { getRequiredID } from "@library/componentIDs";
import NavigationItemCategory from "./NavigationItemCategory";
import NavigationItemList from "./NavigationItemList";
import { IKbCategoryFragment, IKbNavigationItem } from "@knowledge/@types/api";
import { t } from "@library/application";

interface IProps {
    onCategoryNavigate: (categoryID: number) => void;
    onItemSelect: (categoryID: number) => void;
    selectedCategory: IKbCategoryFragment | null;
    navigatedCategory: IKbCategoryFragment | null;
    chosenCategory: IKbCategoryFragment | null;
    items: IKbNavigationItem[];
}

interface IState {
    selectedRecordID?: number;
}

/**
 * Displays the contents of a particular location. Connects NavigationItemList to its data source.
 */
export default class LocationContents extends React.Component<IProps, IState> {
    private legendID;
    private listID;

    public constructor(props) {
        super(props);
        this.legendID = getRequiredID(props, "navigationItemList-legend");
        this.listID = getRequiredID(props, "navigationItemList");
    }

    public render() {
        const { selectedCategory, items, navigatedCategory, chosenCategory } = this.props;
        const title = navigatedCategory ? navigatedCategory.name : t("Knowledge Bases");

        const contents = items.map((item, index) => {
            const isSelected = !!selectedCategory && selectedCategory.knowledgeCategoryID === item.recordID;
            const navigateCallback = () => this.props.onCategoryNavigate(item.recordID);
            const selectCallback = () => this.props.onItemSelect(item.recordID);
            return (
                <NavigationItemCategory
                    key={index}
                    isInitialSelection={!!chosenCategory && item.recordID === chosenCategory.knowledgeCategoryID}
                    isSelected={isSelected}
                    name={this.radioName}
                    value={item}
                    onNavigate={navigateCallback}
                    onSelect={selectCallback}
                />
            );
        });
        return (
            <NavigationItemList
                id={this.listID}
                legendID={this.legendID}
                categoryName={title}
                key={navigatedCategory ? navigatedCategory.knowledgeCategoryID : undefined}
            >
                {contents}
            </NavigationItemList>
        );
    }

    private get radioName(): string {
        return "folders-" + this.listID;
    }

    private setFocusOnLegend() {
        const legend = document.getElementById(this.legendID);
        if (legend) {
            legend.focus();
        }
    }

    /**
     * Check for focus
     */
    public componentDidUpdate() {
        this.setFocusOnLegend();
    }
}
