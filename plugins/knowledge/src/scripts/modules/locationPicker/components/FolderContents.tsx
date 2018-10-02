/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { getRequiredID } from "@library/componentIDs";
import { ILocationPickerProps, withLocationPicker } from "@knowledge/modules/locationPicker/state/context";
import NavigationItem from "./NavigationItem";
import NavigationItemList from "./NavigationItemList";

interface IOwnProps {
    initialCategoryID: number;
}

interface IProps extends IOwnProps, ILocationPickerProps {}

interface IState {
    id: string;
    selectedRecordID?: number;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export class FolderContents extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "locationPicker"),
        };
    }

    public render() {
        const { locationBreadcrumb, currentFolderItems } = this.props;
        const currentCategory = locationBreadcrumb[locationBreadcrumb.length - 1];
        const contents = currentFolderItems.map((item, index) => {
            const isSelected = currentCategory.knowledgeCategoryID === item.recordID;
            const navigateCallback = () => this.props.navigateToCategory(item.recordID);
            return (
                <NavigationItem
                    key={index}
                    isInitialSelection={false}
                    isSelected={isSelected}
                    name={this.radioName}
                    value={item}
                    onNavigate={navigateCallback}
                    onSelect={this.onChange}
                />
            );
        });
        return <NavigationItemList categoryName={currentCategory.name}>{contents}</NavigationItemList>;
    }

    private get radioName(): string {
        return "folders-" + this.state.id;
    }

    private onChange = e => {
        this.setState({
            selectedRecordID: e.currentTarget.value,
        });
    };
}

export default withLocationPicker<IProps>(FolderContents);
