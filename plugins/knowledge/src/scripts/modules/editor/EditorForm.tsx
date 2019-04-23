/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import EditorMenu from "@knowledge/modules/editor/components/EditorMenu";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorPageModel, { IEditorPageForm, IInjectableEditorProps } from "@knowledge/modules/editor/EditorPageModel";
import LocationInput from "@knowledge/modules/locationPicker/LocationInput";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { userContentClasses } from "@library/content/userContentStyles";
import { useMeasure } from "@library/dom/hookUtils";
import AccessibleError from "@library/forms/AccessibleError";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import DocumentTitle from "@library/routing/DocumentTitle";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import { Editor } from "@rich-editor/editor/context";
import EditorContent from "@rich-editor/editor/EditorContent";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import EditorDescriptions from "@rich-editor/editor/pieces/EditorDescriptions";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import classNames from "classnames";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";
import uniqueId from "lodash/uniqueId";
import { DeltaOperation } from "quill/core";
import React, { useCallback, useMemo, useRef, useState } from "react";
import { connect } from "react-redux";
import { RouteComponentProps, withRouter } from "react-router-dom";
import { animated as a, useSpring } from "react-spring";
import Message from "@library/messages/Message";
import { TouchScrollable } from "react-scrolllock";

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
    const { clearConversionNotice } = props.actions;
    const message = t(
        "This text has been converted from another format. As a result you may lose some of your formatting. Do you wish to continue?",
    );
    const conversionNotice = props.notifyConversion && (
        <Message
            className={classNames(classesEditorForm.containerWidth, classesEditorForm.conversionNotice)}
            onCancel={props.history.goBack}
            onConfirm={clearConversionNotice}
            contents={message}
            stringContents={message}
        />
    );

    const canSubmit = (() => {
        const minTitleLength = 1;
        const title = props.form.name || "";

        return (
            title.length >= minTitleLength &&
            props.form.knowledgeCategoryID !== null &&
            !isLoading &&
            !props.notifyConversion
        );
    })();

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

    const contentRef = useRef<HTMLDivElement>(null);
    const contentSize = useMeasure(contentRef);
    const [scrollPos, setScrollPos] = useState(0);
    const embedBarRef = useRef<HTMLDivElement>(null);
    const formRef = useRef<HTMLFormElement>(null);
    const onScroll = useCallback(() => {
        requestAnimationFrame(() => {
            if (!formRef.current) {
                return;
            }
            setScrollPos(Math.max(0, formRef.current.scrollTop));
        });
    }, [setScrollPos, formRef.current]);
    const { y } = useSpring({ y: scrollPos, tension: 100 });
    let start = 0;
    let end = 0;
    if (embedBarRef.current) {
        const rect = embedBarRef.current.getBoundingClientRect();
        start = rect.top / 2;
        end = rect.top + rect.height * 2;
    }

    const opacity = y.interpolate({
        range: [start, end],
        output: [0, 1],
    });

    const boxShadow = y.interpolate({
        range: [start, end],
        output: [shadowHelper().makeShadow(0.2), shadowHelper().makeShadow(0)],
    });

    return (
        <TouchScrollable>
            <form className={classNames(classesEditorForm.root)} onSubmit={onSubmit} onScroll={onScroll} ref={formRef}>
                <a.div
                    className={classesEditorForm.header}
                    style={{
                        boxShadow,
                    }}
                >
                    <EditorHeader
                        canSubmit={canSubmit}
                        isSubmitLoading={props.submit.status === LoadStatus.LOADING}
                        draft={draft}
                        useShadow={false}
                        optionsMenu={
                            article.status === LoadStatus.SUCCESS && article.data ? (
                                <EditorMenu article={article.data} />
                            ) : null
                        }
                        saveDraft={saveDraft}
                    />
                </a.div>

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
                        <div className={classesEditorForm.embedBarBottom} />
                        <a.div
                            className={classesEditorForm.embedBarBottomFull}
                            style={{
                                opacity,
                            }}
                        />
                    </div>

                    {conversionNotice}
                    <div
                        className={classNames(
                            "richEditor",
                            { isDisabled: isLoading },
                            "FormWrapper",
                            classesEditorForm.editor(contentSize.top),
                            classesRichEditor.root,
                            classesEditorForm.containerWidth,
                        )}
                        ref={contentRef}
                        aria-label={t("Type your message.")}
                        aria-describedby={domDescriptionID}
                        role="textbox"
                        aria-multiline={true}
                        id={domID}
                        aria-errormessage={bodyError ? domEditorErrorID : undefined}
                        aria-invalid={!!bodyError}
                    >
                        <EditorDescriptions id={domDescriptionID} />
                        <div className={classNames(classesEditorForm.modernFrame, inheritHeightClass())}>
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
            </form>
        </TouchScrollable>
    );
}

const withRedux = connect(
    EditorPageModel.getInjectableProps,
    dispatch => ({ actions: new EditorPageActions(dispatch, apiv2) }),
);

export default withRedux(withRouter(withDevice(EditorForm)));
