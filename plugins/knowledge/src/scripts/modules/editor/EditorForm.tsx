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
import React, { useMemo, useCallback, useState, useRef } from "react";
import { connect } from "react-redux";
import { RouteComponentProps, withRouter } from "react-router-dom";
import { useSpring, animated as a, interpolate } from "react-spring";
import { useMeasure } from "@library/dom/hookUtils";

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

export function EditorForm(props: IProps) {
    const domID = useMemo(() => uniqueId("editorForm-"), []);
    const domTitleID = domID + "-title";
    const domTitleErrorsID = domTitleID + "Errors";
    const domEditorErrorID = domID + "editorError";
    const domDescriptionID = domID + "description";
    const { article, draft, revision, form, formNeedsRefresh, saveDraft, bodyError } = props;
    const classesRichEditor = richEditorClasses(false);
    const classesEditorForm = editorFormClasses();
    const classesUserContent = userContentClasses();
    const isLoading = [article.status, revision.status, draft.status].includes(LoadStatus.LOADING);

    /**
     * Update the draft from the contents of the form.
     *
     * This it throttled to happen at most and every 10 seconds.
     */
    const updateDraft = useCallback(
        throttle(
            () => {
                void props.actions.syncDraft();
            },
            10000,
            {
                leading: false,
                trailing: true,
            },
        ),
        [props.actions.syncDraft],
    );

    /**
     * Handle changes in the form. Updates the draft.
     */
    const handleFormChange = useCallback(
        (delta: Partial<IEditorPageForm>) => {
            props.actions.updateForm(delta);
            updateDraft();
        },
        [props.actions.updateForm, updateDraft],
    );

    /**
     * Handle changes in the location picker.
     */
    const locationPickerChangeHandler = useCallback(
        (categoryID: number, sort?: number) => {
            props.removeCategoryError();
            handleFormChange({ knowledgeCategoryID: categoryID, sort });
        },
        [props.removeCategoryError, handleFormChange],
    );

    /**
     * Change handler for the editor.
     */
    const editorChangeHandler = useCallback(
        debounce((content: DeltaOperation[]) => {
            handleFormChange({ body: content });
            props.removeBodyError();
        }, 1000 / 60),
        [handleFormChange, props.removeBodyError],
    );
    /**
     * Change handler for the title
     */
    const titleChangeHandler = useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) => {
            props.removeTitleError();
            handleFormChange({ name: event.target.value });
        },
        [props.removeTitleError, handleFormChange],
    );

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    const onSubmit = useCallback(
        (event: React.FormEvent) => {
            event.preventDefault();
            event.stopPropagation();
            void props.actions.publish(props.history);
        },
        [props.actions.publish, props.history],
    );

    const [scrollPos, setScrollPos] = useState(0);
    const embedBarRef = useRef<HTMLDivElement | null>(null);
    const measured = useMeasure(embedBarRef);
    const onScroll = useCallback(e => setScrollPos(e.target.scrollTop), []);
    const windowWidth = window.innerWidth;
    window.embedRef = embedBarRef;
    const { y } = useSpring({ y: scrollPos });
    let start = 0;
    let end = 0;
    if (embedBarRef.current) {
        const rect = embedBarRef.current.getBoundingClientRect();
        start = rect.top;
        end = rect.top + rect.height;
    }

    const opacity = y.interpolate({
        map: Math.abs,
        range: [0, 400],
        output: [0, 1],
        extrapolate: "clamp",
    });

    const width = y.interpolate({
        map: Math.abs,
        range: [start, end],
        output: [measured.width || 0, windowWidth],
        extrapolate: "clamp",
    });

    return (
        <form className={classNames(classesEditorForm.root, inheritHeightClass())} onSubmit={onSubmit}>
            <EditorHeader
                isSubmitLoading={props.submit.status === LoadStatus.LOADING}
                draft={draft}
                optionsMenu={
                    article.status === LoadStatus.SUCCESS && article.data ? <EditorMenu article={article.data} /> : null
                }
                saveDraft={saveDraft}
            />
            <div className={classNames(classesEditorForm.body, inheritHeightClass())} onScroll={onScroll}>
                <div className={classesEditorForm.spacer} />
                <ScreenReaderContent>
                    <h1 id={props.titleID}>{t("Write Discussion")}</h1>
                </ScreenReaderContent>
                <div className="sr-only">
                    <DocumentTitle title={props.form.name || "Untitled"} />
                </div>
                <div className={classesEditorForm.containerWidth}>
                    <LocationInput
                        disabled={isLoading}
                        onChange={locationPickerChangeHandler}
                        error={props.categoryError}
                    />
                    <label>
                        <input
                            id={domTitleID}
                            className={classNames("inputText", classesEditorForm.title)}
                            type="text"
                            placeholder={t("Title")}
                            value={props.form.name || ""}
                            onChange={titleChangeHandler}
                            disabled={isLoading}
                            aria-invalid={!!props.titleError}
                            aria-errormessage={!!props.titleError ? domTitleErrorsID : undefined}
                        />
                        {!!props.titleError && (
                            <AccessibleError
                                id={domTitleErrorsID}
                                error={props.titleError}
                                className={classesEditorForm.titleErrorMessage}
                            />
                        )}
                    </label>
                </div>
                <Editor
                    allowUpload={true}
                    isPrimaryEditor={true}
                    legacyMode={false}
                    onChange={editorChangeHandler}
                    isLoading={isLoading}
                    reinitialize={formNeedsRefresh}
                    initialValue={form.body}
                    operationsQueue={props.editorOperationsQueue}
                    clearOperationsQueue={props.actions.clearEditorOps}
                >
                    <div className={classesEditorForm.embedBarContainer}>
                        <a.div
                            className={classesEditorForm.embedBarTop}
                            style={{
                                opacity,
                            }}
                        />
                        <EditorEmbedBar
                            contentRef={embedBarRef}
                            className={classNames(classesEditorForm.embedBar, classesEditorForm.containerWidth)}
                        />
                        <a.div
                            className={classesEditorForm.embedBarBottom}
                            style={{
                                width,
                            }}
                        />
                    </div>

                    <div
                        className={classNames(
                            "richEditor",
                            { isDisabled: isLoading },
                            "FormWrapper",
                            classesEditorForm.editor,
                            classesRichEditor.root,
                            classesEditorForm.containerWidth,
                            inheritHeightClass(),
                        )}
                        aria-label={t("Type your message.")}
                        aria-describedby={domDescriptionID}
                        role="textbox"
                        aria-multiline={true}
                        id={domID}
                        aria-errormessage={bodyError ? domEditorErrorID : undefined}
                        aria-invalid={!!bodyError}
                    >
                        <EditorDescriptions id={domDescriptionID} />
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
                                        id={domEditorErrorID}
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

const withRedux = connect(
    EditorPageModel.getInjectableProps,
    dispatch => ({ actions: new EditorPageActions(dispatch, apiv2) }),
);

export default withRedux(withRouter(withDevice<IProps>(EditorForm)));
