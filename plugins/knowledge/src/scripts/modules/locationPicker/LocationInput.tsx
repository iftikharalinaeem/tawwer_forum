/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import LocationBreadcrumbs from "@knowledge/modules/locationPicker/components/LocationBreadcrumbs";
import LocationPicker from "@knowledge/modules/locationPicker/LocationPicker";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { categoryIcon, plusCircle } from "@library/components/icons/common";
import { Modal } from "@library/components/modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import classNames from "classnames";
import * as React from "react";
import { connect } from "react-redux";
import { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import isEqual from "lodash/isEqual";

/**
 * This component allows to display and edit the location of the current page.
 * Creates a location picker in a modal when activated.
 */
export class LocationInput extends React.PureComponent<IProps, IState> {
    private changeLocationButton: React.RefObject<HTMLButtonElement> = React.createRef();
    private static readonly SELECT_MESSAGE = t("Select a Category");

    public state: IState = {
        showLocationPicker: false,
    };

    public render() {
        const { className, ...passThrough } = this.props;
        const { locationBreadcrumb } = this.props;
        const buttonTitle = locationBreadcrumb
            ? LocationBreadcrumbs.renderString(locationBreadcrumb)
            : LocationInput.SELECT_MESSAGE;

        const buttonContents = locationBreadcrumb ? (
            <LocationBreadcrumbs locationData={locationBreadcrumb} icon={categoryIcon("pageLocation-icon")} />
        ) : (
            <React.Fragment>
                {plusCircle("pageLocation-icon")}
                {LocationInput.SELECT_MESSAGE}
            </React.Fragment>
        );

        return (
            <React.Fragment>
                <div className={classNames("pageLocation", this.props.className)}>
                    <Button
                        title={buttonTitle}
                        type="button"
                        aria-label={t("Page Location")}
                        className="pageLocation-picker"
                        onClick={this.showLocationPicker}
                        baseClass={ButtonBaseClass.CUSTOM}
                        buttonRef={this.changeLocationButton}
                        disabled={!!this.props.disabled}
                    >
                        {buttonContents}
                    </Button>
                </div>
                {this.state.showLocationPicker && (
                    <Modal
                        exitHandler={this.hideLocationPicker}
                        size={ModalSizes.SMALL}
                        className={classNames(this.props.className)}
                        label={t("Choose a location for this page.")}
                        elementToFocusOnExit={this.changeLocationButton.current!}
                    >
                        <LocationPicker
                            afterChoose={this.handleChoose}
                            onCloseClick={this.hideLocationPicker}
                            {...passThrough}
                        />
                    </Modal>
                )}
            </React.Fragment>
        );
    }

    public componentDidMount() {
        if (this.props.initialRecord) {
            void this.props.initLocationPickerFromRecord(this.props.initialRecord);
        }
    }

    private handleChoose = () => {
        this.hideLocationPicker();
    };

    /**
     * Show the location picker modal.
     */
    private showLocationPicker = () => {
        this.setState({
            showLocationPicker: true,
        });
    };

    /**
     * Hiders the location picker modal.
     */
    private hideLocationPicker = () => {
        this.setState({
            showLocationPicker: false,
        });
    };

    public componentDidUpdate(prevProps, prevState) {
        if (prevState.showLocationPicker !== this.state.showLocationPicker) {
            this.forceUpdate();
        }

        if (!isEqual(this.props.chosenRecord, prevProps.chosenRecord) && this.props.onChange) {
            this.props.onChange(this.props.chosenCategoryID);
        }
    }
}

interface IOwnProps {
    className?: string;
    initialRecord?: ILocationPickerRecord | null;
    disabled?: boolean;
    onChange?: (categoryID: number | null) => void;
}

interface IState {
    showLocationPicker: boolean;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { knowledgeBasesByID } = state.knowledge.knowledgeBases;
    const { chosenRecord } = state.knowledge.locationPicker;
    const { navigationItems } = state.knowledge.navigation;
    let chosenCategoryID: number | null = null;
    if (chosenRecord) {
        if (chosenRecord.recordType === KbRecordType.CATEGORY) {
            chosenCategoryID = chosenRecord.recordID;
        }

        if (
            chosenRecord.recordType === KbRecordType.KB &&
            knowledgeBasesByID.data &&
            knowledgeBasesByID.data[chosenRecord.recordID]
        ) {
            chosenCategoryID = knowledgeBasesByID.data[chosenRecord.recordID].rootCategoryID;
        }
    }
    return {
        chosenRecord,
        chosenCategoryID,
        locationBreadcrumb:
            chosenCategoryID !== null
                ? NavigationSelector.selectBreadcrumb(navigationItems, KbRecordType.CATEGORY + chosenCategoryID)
                : null,
    };
}

function mapDispatchToProps(dispatch: any) {
    const lpActions = new LocationPickerActions(dispatch, apiv2);
    return {
        initLocationPickerFromRecord: lpActions.initLocationPickerFromRecord,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(LocationInput);
