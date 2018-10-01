/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { getRequiredID } from "@library/componentIDs";
import Button from "@dashboard/components/forms/Button";
import { rightChevron } from "@library/components/Icons";
import { check } from "@library/components/Icons";
import { IKbNavigationItem, IKbCategoryFragment } from "@knowledge/@types/api";
import { LoadStatus } from "@library/@types/api";
import { model, thunks, actions } from "@knowledge/modules/locationPicker/state";
import { IStoreState } from "@knowledge/state/model";
import { bindActionCreators } from "redux";
import { connect } from "react-redux";

interface IOwnProps {
    initialCategoryID: number;
}

interface IReduxProps {
    locationBreadcrumb: IKbCategoryFragment[];
    currentFolderItems: IKbNavigationItem[];
    status: LoadStatus;
}

interface IReduxDispatch {
    getKbNavigation: typeof thunks.getKbNavigation;
    resetNavigation: typeof actions.resetNavigation;
    navigateToCategory: typeof actions.navigateToCategory;
}

interface IProps extends IOwnProps, IReduxProps, IReduxDispatch {}

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

    public tempClick = () => {
        alert("do click");
    };

    public get radioName(): string {
        return "folders-" + this.state.id;
    }

    public render() {
        const { locationBreadcrumb, currentFolderItems, initialCategoryID } = this.props;
        const currentCategory = locationBreadcrumb[locationBreadcrumb.length - 1];
        const contents = currentFolderItems.map(item => {
            const isSelected = currentCategory.knowledgeCategoryID === item.recordID;
            return (
                <li className="folderContents-item">
                    <label className="folderContents-folder">
                        <input
                            type="radio"
                            className={classNames("folderContents-input", {
                                initialSelection:
                                    item.recordType === "knowledgeCategory" && initialCategoryID === item.recordID,
                            })}
                            name={this.radioName}
                            value={item.recordID}
                            checked={isSelected}
                            onChange={this.onChange}
                        />
                        <span className="dropDownRadio-check" aria-hidden={true}>
                            {isSelected && check()}
                        </span>
                        <span className="dropDownRadio-label">{item.name}</span>
                    </label>
                    {item.recordType === "knowledgeCategory" &&
                        item.children &&
                        item.children.length > 0 && (
                            <Button onClick={this.tempClick}>
                                {rightChevron()}
                                <span className="sr-only">{t("Sub folder")}</span>
                            </Button>
                        )}
                </li>
            );
        });
        return (
            <fieldset className={classNames("folderContents")}>
                <legend className="sr-only">{t("Contents of folder: " + currentCategory.name)}</legend>
                <ul className="folderContents-items">{contents}</ul>
            </fieldset>
        );
    }

    private onChange = e => {
        this.setState({
            selectedRecordID: e.currentTarget.value,
        });
    };
}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState): IReduxProps {
    return {
        locationBreadcrumb: model.getCurrentLocationBreadcrumb(state),
        currentFolderItems: state.knowledge.locationPicker.currentFolderItems,
        status: state.knowledge.locationPicker.status,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch): IReduxDispatch {
    const { getKbNavigation } = thunks;
    const { resetNavigation, navigateToCategory } = actions;
    return bindActionCreators({ getKbNavigation, resetNavigation, navigateToCategory }, dispatch);
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(FolderContents);
