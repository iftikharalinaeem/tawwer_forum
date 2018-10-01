/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { getRequiredID } from "@library/componentIDs";
import { ILocationPickerProps, withLocationPicker } from "@knowledge/modules/locationPicker/state/context";
import NavigationItem from "@knowledge/components/locationPicker/NavigationItem";
import NavigationItemList from "@knowledge/components/locationPicker/NavigationItemList";

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
        const contents = currentFolderItems.map(item => {
            const isSelected = currentCategory.knowledgeCategoryID === item.recordID;
            return (
                <NavigationItem
                    isInitialSelection={false}
                    isSelected={isSelected}
                    name={this.radioName}
                    value={item}
                    onNavigate={this.tempClick}
                    onSelect={this.onChange}
                />
            );
        });
        return <NavigationItemList categoryName={currentCategory.name}>{contents}</NavigationItemList>;
    }

    private tempClick = () => {
        alert("do click");
    };

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
