/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { getRequiredID } from "@library/componentIDs";
import { ILocationPickerProps, withLocationPicker } from "@knowledge/modules/locationPicker/state";
import NavigationItemCategory from "./NavigationItemCategory";
import NavigationItemList from "./NavigationItemList";
import { IKbCategoryFragment, IKbNavigationItem } from "@knowledge/@types/api";
import { ILoadable, LoadStatus } from "@library/@types/api";
import MediumLoader from "@library/components/MediumLoader";

interface IProps {
    initialCategoryID: number;
    onCategoryNavigate: (categoryID: number) => void;
    onItemSelect: (categoryID: number) => void;
    selectedCategory: IKbCategoryFragment;
    items: ILoadable<IKbNavigationItem[]>;
}

interface IState {
    id: string;
    selectedRecordID?: number;
}

/**
 * Displays the contents of a particular location. Connects NavigationItemList to its data source.
 */
export default class LocationContents extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "locationPicker"),
        };
    }

    public render() {
        const { selectedCategory, items } = this.props;

        if (items.status === LoadStatus.SUCCESS) {
            const contents = items.data.map((item, index) => {
                const isSelected = selectedCategory.knowledgeCategoryID === item.recordID;
                const navigateCallback = () => this.props.onCategoryNavigate(item.recordID);
                const selectCallback = () => this.props.onItemSelect(item.recordID);
                return (
                    <NavigationItemCategory
                        key={index}
                        isInitialSelection={false}
                        isSelected={isSelected}
                        name={this.radioName}
                        value={item}
                        onNavigate={navigateCallback}
                        onSelect={selectCallback}
                    />
                );
            });
            return <NavigationItemList categoryName={selectedCategory.name}>{contents}</NavigationItemList>;
        } else {
            return (
                <div className="folderContents">
                    <MediumLoader />
                </div>
            );
        }
    }

    private get radioName(): string {
        return "folders-" + this.state.id;
    }
}
