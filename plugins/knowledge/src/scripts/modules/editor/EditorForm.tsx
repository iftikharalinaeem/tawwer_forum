/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import EditorMenu from "@knowledge/modules/editor/components/EditorMenu";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorPageModel, { IEditorPageForm, IInjectableEditorProps } from "@knowledge/modules/editor/EditorPageModel";
import LocationInput from "@knowledge/modules/locationPicker/LocationInput";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Container from "@library/layout/components/Container";
import { withDevice, IDeviceProps } from "@library/layout/DeviceContext";
import PanelLayout from "@library/layout/PanelLayout";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import DocumentTitle from "@library/routing/DocumentTitle";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import { Editor } from "@rich-editor/editor/Editor";
import classNames from "classnames";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";
import { DeltaOperation } from "quill/core";
import React from "react";
import { connect } from "react-redux";
import { RouteComponentProps, withRouter } from "react-router-dom";
import { richEditorFormClasses } from "@rich-editor/editor/richEditorFormClasses";
import uniqueId from "lodash/uniqueId";

interface IProps extends IInjectableEditorProps, IDeviceProps, RouteComponentProps<any> {
    actions: EditorPageActions;
    className?: string;
    titleID?: string;
    mobileDropDownTitle?: string;
    kbID?: number;
    errors: {
        category?: string;
        title?: string;
        body?: string;
    };
}

/**
 * Form for the editor page.
 */
export class EditorForm extends React.PureComponent<IProps> {
    private editorRef: React.RefObject<Editor> = React.createRef();

    private domID: string = uniqueId("editorForm-");
    private domTitleID: string = this.domID + "-title";
    private domTitleErrorsID: string = this.domTitleID + "Errors";

    /**
     * @inheritdoc
     */
    public render() {
        const { article, draft, form, formNeedsRefresh, saveDraft } = this.props;
        const classesRichEditorForm = richEditorFormClasses();
        const errors = this.props.errors ? this.props.errors : {};

        return (
            <form
                className={classNames("richEditorForm", inheritHeightClass(), classesRichEditorForm.root)}
                onSubmit={this.onSubmit}
            >
                <EditorHeader
                    isSubmitLoading={this.props.submit.status === LoadStatus.LOADING}
                    className={classNames("richEditorForm-header")}
                    draft={draft}
                    optionsMenu={
                        article.status === LoadStatus.SUCCESS && article.data ? (
                            <EditorMenu article={article.data} />
                        ) : null
                    }
                    saveDraft={saveDraft}
                />
                <Container className={classNames("richEditorForm-body", classesRichEditorForm.body)}>
                    <ScreenReaderContent>
                        <h1 id={this.props.titleID}>{t("Write Discussion")}</h1>
                    </ScreenReaderContent>
                    <PanelLayout
                        className="isOneCol"
                        growMiddleBottom={true}
                        device={this.props.device}
                        topPadding={false}
                        middleBottom={
                            <div className={classesRichEditorForm.formContent}>
                                <LocationInput
                                    disabled={this.isLoading}
                                    onChange={this.locationPickerChangeHandler}
                                    error={errors.category ? errors.category : undefined}
                                />
                                <div className="sr-only">
                                    <DocumentTitle title={this.props.form.name || "Untitled"} />
                                </div>
                                <input
                                    id={this.domTitleID}
                                    className={classNames(
                                        "richEditorForm-title",
                                        "inputBlock-inputText",
                                        "inputText",
                                        classesRichEditorForm.title,
                                    )}
                                    type="text"
                                    placeholder={t("Title")}
                                    value={this.props.form.name || ""}
                                    onChange={this.titleChangeHandler}
                                    disabled={this.isLoading}
                                    aria-invalid={!!errors.title}
                                    aria-errormessage={!!errors.title ? this.domTitleErrorsID : undefined}
                                />
                                <Editor
                                    allowUpload={true}
                                    ref={this.editorRef}
                                    isPrimaryEditor={true}
                                    onChange={this.editorChangeHandler}
                                    className={classNames(
                                        "FormWrapper",
                                        "inheritHeight",
                                        "richEditorForm-editor",
                                        inheritHeightClass(),
                                    )}
                                    isLoading={this.isLoading}
                                    device={this.props.device}
                                    legacyMode={false}
                                    reinitialize={formNeedsRefresh}
                                    initialValue={form.body}
                                    operationsQueue={this.props.editorOperationsQueue}
                                    clearOperationsQueue={this.props.actions.clearEditorOps}
                                    error={errors.body}
                                />
                            </div>
                        }
                    />
                </Container>
            </form>
        );
    }

    /**
     * Determine if the form is loading data or not.
     */
    private get isLoading(): boolean {
        return this.propsAreLoading(this.props);
    }

    /**
     * Determine from a set of props if the component should display as loading or now.
     */
    private propsAreLoading(props: IProps): boolean {
        const { article, revision, draft } = props;
        return [article.status, revision.status, draft.status].includes(LoadStatus.LOADING);
    }

    /**
     * Handle changes in the form. Updates the draft.
     */
    private handleFormChange(form: Partial<IEditorPageForm>) {
        this.props.actions.updateForm(form);
        this.updateDraft();
    }

    /**
     * Handle changes in the location picker.
     */
    private locationPickerChangeHandler = (categoryID: number, sort?: number) => {
        this.handleFormChange({ knowledgeCategoryID: categoryID, sort });
    };

    /**
     * Change handler for the editor.
     */
    private editorChangeHandler = debounce((content: DeltaOperation[]) => {
        this.handleFormChange({ body: content });
    }, 1000 / 60);

    /**
     * Update the draft from the contents of the form.
     *
     * This it throttled to happen at most and every 10 seconds.
     */
    private updateDraft = throttle(
        () => {
            void this.props.actions.syncDraft();
        },
        10000,
        {
            leading: false,
            trailing: true,
        },
    );

    /**
     * Change handler for the title
     */
    private titleChangeHandler = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.handleFormChange({ name: event.target.value });
    };

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        event.stopPropagation();
        void this.props.actions.publish(this.props.history);
    };
}

const withRedux = connect(
    EditorPageModel.getInjectableProps,
    dispatch => ({ actions: new EditorPageActions(dispatch, apiv2) }),
);

export default withRedux(withRouter(withDevice<IProps>(EditorForm)));
