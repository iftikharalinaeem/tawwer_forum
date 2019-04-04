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
import { userContentClasses } from "@library/content/userContentStyles";
import AccessibleError from "@library/forms/AccessibleError";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import DocumentTitle from "@library/routing/DocumentTitle";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import { Editor } from "@rich-editor/editor/context";
import EditorContent from "@rich-editor/editor/EditorContent";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import EditorDescriptions from "@rich-editor/editor/pieces/EditorDescriptions";
import { EditorEmbedBar } from "@rich-editor/editor/pieces/EmbedBar";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";
import classNames from "classnames";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";
import uniqueId from "lodash/uniqueId";
import { DeltaOperation } from "quill/core";
import React from "react";
import { connect } from "react-redux";
import { RouteComponentProps, withRouter } from "react-router-dom";

interface IProps extends IInjectableEditorProps, IDeviceProps, RouteComponentProps<any> {
    actions: EditorPageActions;
    className?: string;
    titleID?: string;
    mobileDropDownTitle?: string;
    kbID?: number;
    categoryError?: string;
    titleError?: string;
    bodyError?: string;
    removeCategoryError: () => void;
    removeTitleError: () => void;
    removeBodyError: () => void;
}

/**
 * Form for the editor page.
 */
export class EditorForm extends React.PureComponent<IProps> {
    private domID: string = uniqueId("editorForm-");
    private domTitleID: string = this.domID + "-title";
    private domTitleErrorsID: string = this.domTitleID + "Errors";
    private domEditorErrorID: string = this.domID + "editorError";
    private domDescriptionID: string = this.domID + "description";
    /**
     * @inheritdoc
     */
    public render() {
        const { article, draft, form, formNeedsRefresh, saveDraft, bodyError } = this.props;
        const classesRichEditor = richEditorClasses(false);
        const classesEditorForm = editorFormClasses();
        const classesUserContent = userContentClasses();

        return (
            <form className={classNames(classesEditorForm.root, inheritHeightClass())} onSubmit={this.onSubmit}>
                <EditorHeader
                    isSubmitLoading={this.props.submit.status === LoadStatus.LOADING}
                    draft={draft}
                    optionsMenu={
                        article.status === LoadStatus.SUCCESS && article.data ? (
                            <EditorMenu article={article.data} />
                        ) : null
                    }
                    saveDraft={saveDraft}
                />
                <div className={classesEditorForm.body}>
                    <ScreenReaderContent>
                        <h1 id={this.props.titleID}>{t("Write Discussion")}</h1>
                    </ScreenReaderContent>

                    <LocationInput
                        disabled={this.isLoading}
                        onChange={this.locationPickerChangeHandler}
                        error={this.props.categoryError}
                    />
                    <div className="sr-only">
                        <DocumentTitle title={this.props.form.name || "Untitled"} />
                    </div>
                    <label>
                        <input
                            id={this.domTitleID}
                            className={classNames("inputText", classesEditorForm.title)}
                            type="text"
                            placeholder={t("Title")}
                            value={this.props.form.name || ""}
                            onChange={this.titleChangeHandler}
                            disabled={this.isLoading}
                            aria-invalid={!!this.props.titleError}
                            aria-errormessage={!!this.props.titleError ? this.domTitleErrorsID : undefined}
                        />
                        {!!this.props.titleError && (
                            <AccessibleError
                                id={this.domTitleErrorsID}
                                error={this.props.titleError}
                                className={classesEditorForm.titleErrorMessage}
                            />
                        )}
                    </label>
                    <Editor
                        allowUpload={true}
                        isPrimaryEditor={true}
                        legacyMode={false}
                        onChange={this.editorChangeHandler}
                        isLoading={this.isLoading}
                        reinitialize={formNeedsRefresh}
                        initialValue={form.body}
                        operationsQueue={this.props.editorOperationsQueue}
                        clearOperationsQueue={this.props.actions.clearEditorOps}
                    >
                        <EditorEmbedBar className={classesEditorForm.inlineMenuItems} />
                        <div
                            className={classNames(
                                "richEditor",
                                { isDisabled: this.isLoading },
                                "FormWrapper",
                                classesEditorForm.editor,
                                classesRichEditor.root,
                                inheritHeightClass(),
                            )}
                            aria-label={t("Type your message.")}
                            aria-describedby={this.domDescriptionID}
                            role="textbox"
                            aria-multiline={true}
                            id={this.domID}
                            aria-errormessage={bodyError ? this.domEditorErrorID : undefined}
                            aria-invalid={!!bodyError}
                        >
                            <EditorDescriptions id={this.domDescriptionID} />
                            <div
                                className={classNames(
                                    "richEditor-frame",
                                    "InputBox",
                                    "isMenuInset",
                                    classesEditorForm.modernFrame,
                                )}
                            >
                                <>
                                    {bodyError && (
                                        <AccessibleError
                                            id={this.domEditorErrorID}
                                            ariaHidden={true}
                                            error={bodyError}
                                            className={classesEditorForm.bodyErrorMessage}
                                            paragraphClassName={classesEditorForm.categoryErrorParagraph}
                                            wrapClassName={classesUserContent.root}
                                        />
                                    )}
                                    <EditorContent />
                                    <EditorInlineMenus />
                                    <EditorParagraphMenu />
                                </>
                            </div>
                        </div>
                    </Editor>
                </div>
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
        this.props.removeCategoryError();
        this.handleFormChange({ knowledgeCategoryID: categoryID, sort });
    };

    /**
     * Change handler for the editor.
     */
    private editorChangeHandler = debounce((content: DeltaOperation[]) => {
        this.handleFormChange({ body: content });
        this.props.removeBodyError();
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
        this.props.removeTitleError();
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
