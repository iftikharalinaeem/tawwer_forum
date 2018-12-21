/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { getRequiredID, uniqueIDFromPrefix } from "@library/componentIDs";
import LocationPickerItem from "./LocationPickerItem";
import LocationPickerItemList from "./LocationPickerItemList";
import { IKbCategoryFragment } from "@knowledge/@types/api";
import { t } from "@library/application";
import { INavigationTreeItem } from "@library/@types/api";

interface IProps {
    onCategoryNavigate: (categoryID: number) => void;
    onItemSelect: (categoryID: number) => void;
    selectedCategory: IKbCategoryFragment | null;
    navigatedCategory: IKbCategoryFragment | null;
    chosenCategory: IKbCategoryFragment | null;
    items: INavigationTreeItem[];
}

/**
 * Displays the contents of a particular location. Connects NavigationItemList to its data source.
 */
export default class LocationContents extends React.Component<IProps> {
    private legendRef = React.createRef<HTMLLegendElement>();
    private listID = uniqueIDFromPrefix("navigationItemList");

    public render() {
        const { selectedCategory, items, navigatedCategory, chosenCategory } = this.props;
        const title = navigatedCategory ? navigatedCategory.name : t("Knowledge Bases");

        const contents = items.map((item, index) => {
            const isSelected = !!selectedCategory && selectedCategory.knowledgeCategoryID === item.recordID;
            const navigateCallback = () => this.props.onCategoryNavigate(item.recordID);
            const selectCallback = () => this.props.onItemSelect(item.recordID);
            return (
                <LocationPickerItem
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
            <LocationPickerItemList
                id={this.listID}
                legendRef={this.legendRef}
                categoryName={title}
                key={navigatedCategory ? navigatedCategory.knowledgeCategoryID : undefined}
            >
                {contents}
            </LocationPickerItemList>
        );
    }

    private get radioName(): string {
        return "folders-" + this.listID;
    }

    private setFocusOnLegend() {
        this.legendRef.current!.focus();
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        this.setFocusOnLegend();
    }

    /**
     * @inheritdoc
     */
    public componentDidUpdate(prevProps: IProps) {
        if (prevProps.navigatedCategory !== this.props.navigatedCategory) {
            this.setFocusOnLegend();
        }
    }
}
