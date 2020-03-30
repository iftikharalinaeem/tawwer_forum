/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import LocationPicker from "@knowledge/modules/locationPicker/LocationPicker";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import ModalSizes from "@library/modal/ModalSizes";
import classNames from "classnames";
import * as React from "react";
import { connect } from "react-redux";
import { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import isEqual from "lodash/isEqual";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Modal from "@library/modal/Modal";
import uniqueId from "lodash/uniqueId";
import AccessibleError from "@library/forms/AccessibleError";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { CategoryIcon, PlusCircleIcon } from "@library/icons/common";
import { locationPickerClasses } from "@knowledge/modules/locationPicker/locationPickerStyles";
import LocationBreadcrumbs from "@library/navigation/LocationBreadcrumbs";
import { pageLocationClasses } from "@knowledge/modules/locationPicker/pageLocationStyles";

/**
 * This component allows to display and edit the location of the current page.
 * Creates a location picker in a modal when activated.
 */
export class LocationInput extends React.PureComponent<IProps, IState> {
    private changeLocationButton: React.RefObject<HTMLButtonElement> = React.createRef();
    private static readonly SELECT_MESSAGE = t("Select a Category");
    private domID = uniqueId("locationInput-");
    private domErrorID = this.domID + "errors";

    public state: IState = {
        showLocationPicker: false,
    };

    public render() {
        const { inputClassName, modalClassName, ...passThrough } = this.props;
        const { locationBreadcrumb } = this.props;
        const buttonTitle = locationBreadcrumb
            ? LocationBreadcrumbs.renderString(locationBreadcrumb)
            : LocationInput.SELECT_MESSAGE;
        const classesEditorForm = editorFormClasses();
        const classesLocationPicker = locationPickerClasses();
        const classesPageLocation = pageLocationClasses();

        const buttonContents = locationBreadcrumb ? (
            <LocationBreadcrumbs
                locationData={locationBreadcrumb}
                icon={<CategoryIcon className={"pageLocation-icon"} />}
            />
        ) : (
            <React.Fragment>
                <span className={classesLocationPicker.iconWrapper}>
                    <PlusCircleIcon className={"pageLocation-icon"} />
                </span>
                <span className={classesLocationPicker.initialText}>{LocationInput.SELECT_MESSAGE}</span>
            </React.Fragment>
        );

        return (
            <>
                <label className={classNames(classesPageLocation.root, inputClassName)}>
                    <Button
                        id={this.domID}
                        title={buttonTitle}
                        aria-label={t("Page Location")}
                        className={classesPageLocation.picker}
                        onClick={this.showLocationPicker}
                        baseClass={ButtonTypes.CUSTOM}
                        buttonRef={this.changeLocationButton}
                        disabled={!!this.props.disabled}
                        aria-invalid={!!this.props.error}
                        aria-errormessage={this.props.error ? this.domErrorID : undefined}
                    >
                        {buttonContents}
                    </Button>
                    {!!this.props.error && (
                        <AccessibleError
                            id={this.domErrorID}
                            error={this.props.error}
                            paragraphClassName={classesEditorForm.categoryErrorParagraph}
                        />
                    )}
                </label>
                <Modal
                    isVisible={this.state.showLocationPicker}
                    exitHandler={this.hideLocationPicker}
                    size={ModalSizes.SMALL}
                    className={classNames(modalClassName)}
                    label={t("Choose a location for this page.")}
                    elementToFocusOnExit={this.changeLocationButton.current!}
                    // scrollable={true}
                >
                    <LocationPicker
                        afterChoose={this.handleChoose}
                        onCloseClick={this.hideLocationPicker}
                        {...passThrough}
                    />
                </Modal>
            </>
        );
    }

    public componentDidMount() {
        if (this.props.initialRecord) {
            void this.props.initLocationPickerFromRecord(this.props.initialRecord, null);
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

        if (this.props.onChange) {
            const categoryChanged = !isEqual(this.props.chosenRecord, prevProps.chosenRecord);

            if (categoryChanged) {
                const sort = this.props.chosenSort !== null ? this.props.chosenSort : undefined;
                this.props.onChange(this.props.chosenCategoryID, sort);
            }
        }
    }
}

interface IOwnProps {
    inputClassName?: string;
    modalClassName?: string;
    initialRecord?: ILocationPickerRecord | null;
    disabled?: boolean;
    onChange?: (categoryID: number | null, sort?: number) => void;
    error?: string | null;
}

interface IState {
    showLocationPicker: boolean;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { knowledgeBasesByID } = state.knowledge.knowledgeBases;
    const { chosenRecord } = state.knowledge.locationPicker;
    const { navigationItems } = state.knowledge.navigation;
    let chosenCategoryID: number | null = null;
    let locationBreadcrumb: ICrumb[] | null = null;
    let chosenSort: number | null = null;
    if (chosenRecord) {
        if (chosenRecord.recordType === KbRecordType.CATEGORY) {
            chosenCategoryID = chosenRecord.recordID;
            locationBreadcrumb =
                chosenRecord.recordID !== null
                    ? NavigationSelector.selectBreadcrumb(
                          navigationItems,
                          KbRecordType.CATEGORY + chosenRecord.recordID,
                      )
                    : null;
            if (chosenRecord.position !== undefined) {
                chosenSort = chosenRecord.position;
            }
        } else if (
            chosenRecord.recordType === KbRecordType.KB &&
            knowledgeBasesByID.data &&
            knowledgeBasesByID.data[chosenRecord.recordID]
        ) {
            const knowledgeBase = knowledgeBasesByID.data[chosenRecord.recordID];
            chosenCategoryID = knowledgeBase.rootCategoryID;
            locationBreadcrumb = [
                {
                    name: knowledgeBase.name,
                    url: knowledgeBase.url,
                },
            ];
            if (chosenRecord.position !== undefined) {
                chosenSort = chosenRecord.position;
            }
        }
    }
    return {
        chosenRecord,
        chosenSort,
        chosenCategoryID,
        locationBreadcrumb,
    };
}

function mapDispatchToProps(dispatch: any) {
    const lpActions = new LocationPickerActions(dispatch, apiv2);
    return {
        initLocationPickerFromRecord: lpActions.initLocationPickerFromRecord,
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(LocationInput);
