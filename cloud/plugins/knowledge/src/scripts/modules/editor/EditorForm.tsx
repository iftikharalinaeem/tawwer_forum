/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import EditorMenu from "@knowledge/modules/editor/components/EditorMenu";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorPageModel, { IEditorPageForm } from "@knowledge/modules/editor/EditorPageModel";
import LocationInput from "@knowledge/modules/locationPicker/LocationInput";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { useLocaleInfo, LocaleDisplayer, ILocale, loadLocales } from "@vanilla/i18n";
import AccessibleError from "@library/forms/AccessibleError";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import DocumentTitle from "@library/routing/DocumentTitle";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import { Editor } from "@rich-editor/editor/Editor";
import EditorContent from "@rich-editor/editor/EditorContent";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import { EditorInlineMenus } from "@rich-editor/editor/EditorInlineMenus";
import { EditorParagraphMenu } from "@rich-editor/editor/EditorParagraphMenu";
import EditorDescriptions from "@rich-editor/editor/pieces/EditorDescriptions";
import classNames from "classnames";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";
import uniqueId from "lodash/uniqueId";
import { DeltaOperation } from "quill/core";
import React, { useCallback, useMemo, useRef, useState } from "react";
import { connect } from "react-redux";
import { RouteComponentProps, withRouter } from "react-router-dom";
import { animated, useSpring } from "react-spring";
import Message from "@library/messages/Message";
import { TouchScrollable } from "react-scrolllock";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { EDITOR_SCROLL_CONTAINER_CLASS } from "@rich-editor/quill/ClipboardModule";
import { useMeasure } from "@vanilla/react-utils";
import { userContentClasses } from "@library/content/userContentStyles";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import Translate from "@library/content/Translate";
import { WarningIcon } from "@library/icons/common";
import { messagesClasses } from "@library/messages/messageStyles";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";

export function EditorForm(props: IProps) {
    const domID = useMemo(() => uniqueId("editorForm-"), []);
    const domTitleID = domID + "-title";
    const domTitleErrorsID = domTitleID + "Errors";
    const domEditorErrorID = domID + "editorError";
    const domDescriptionID = domID + "description";
    const { article, draft, revision, form, formNeedsRefresh, saveDraft, formErrors } = props;
    const classesRichEditor = richEditorClasses(false);
    const classesEditorForm = editorFormClasses();
    const classesUserContent = userContentClasses();
    const isLoading = [article.status, revision.status, draft.status].includes(LoadStatus.LOADING);
    const updateDraft = useDraftThrottling(props.actions.syncDraft);

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
            handleFormChange({ knowledgeCategoryID: categoryID, sort });
        },
        [handleFormChange],
    );

    /**
     * Change handler for the editor.
     */
    const editorChangeHandler = useCallback(
        debounce((content: DeltaOperation[]) => {
            handleFormChange({ body: content });
        }, 1000 / 60),
        [handleFormChange],
    );
    /**
     * Change handler for the title
     */
    const titleChangeHandler = useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) => {
            handleFormChange({ name: event.target.value });
        },
        [handleFormChange],
    );

    const categoryError = formErrors.knowledgeCategoryID;
    const titleError = formErrors.name || false;
    const bodyError = formErrors.body;
    const canSubmit = !isLoading && !props.notifyConversion && !categoryError && !titleError && !bodyError;
    const sourceLocale = useLocaleInfo();
    const classesMessages = messagesClasses();

    const { pushSmartLocation } = useLinkContext();
    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    const onSubmit = useCallback(
        (event: React.FormEvent) => {
            event.preventDefault();
            event.stopPropagation();
            void props.actions.publish(props.history, pushSmartLocation);
        },
        [props.actions.publish, props.history, pushSmartLocation],
    );

    const { clearConversionNotice } = props.actions;
    const message = t(
        "This text has been converted from another format. As a result you may lose some of your formatting. Do you wish to continue?",
    );
    const conversionNotice = props.notifyConversion && (
        <Message
            className={classNames(classesEditorForm.containerWidth, classesEditorForm.conversionNotice)}
            onCancel={props.history.goBack}
            onConfirm={() => clearConversionNotice()}
            contents={message}
            stringContents={message}
        />
    );

    const translationNotice = props.fallbackLocale.notify && props.fallbackLocale.locale && (
        <Message
            className={classNames(classesEditorForm.containerWidth, classesEditorForm.conversionNotice)}
            icon={<WarningIcon />}
            contents={t(
                "This article hasn't been translated yet. The original article text has been loaded to aid translation.",
            )}
            onConfirm={() => props.actions.clearFallbackLocaleNotice()}
            stringContents={t(
                "This article hasn't been translated yet. The original article text has been loaded to aid translation.",
            )}
        />
    );

    const articleRedirectionNotice = props.notifyArticleRedirection && (
        <Message
            className={classNames(classesEditorForm.containerWidth)}
            icon={<WarningIcon />}
            contents={<Translate source="You have been redirected to the source locale to insert the article." />}
            onConfirm={() => props.actions.notifyRedirection({ shouldNotify: false })}
            stringContents={t("You have been redirected to the source locale to insert the article.")}
        />
    );
    const contentRef = useRef<HTMLDivElement>(null);
    const embedBarRef = useRef<HTMLDivElement>(null);
    const formRef = useRef<HTMLFormElement>(null);
    const contentSize = useMeasure(contentRef);
    const transition = useFormScrollTransition(formRef, embedBarRef);

    return (
        <TouchScrollable>
            <form
                className={classNames(classesEditorForm.root, EDITOR_SCROLL_CONTAINER_CLASS)}
                onSubmit={onSubmit}
                onScroll={transition.scrollHandler}
                ref={formRef}
                autoComplete="off"
            >
                <animated.div
                    className={classesEditorForm.header}
                    style={{
                        boxShadow: transition.headerBoxShadow,
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
                </animated.div>

                <div className={classesEditorForm.spacer} />
                <ScreenReaderContent>
                    <h1 id={props.titleID}>{t("Write Discussion")}</h1>
                </ScreenReaderContent>
                <div className="sr-only">
                    <DocumentTitle title={props.form.name || "Untitled"} />
                </div>
                <main>
                    <div className={classesEditorForm.containerWidth}>
                        <LocationInput
                            disabled={isLoading}
                            onChange={locationPickerChangeHandler}
                            error={categoryError}
                            inputClassName={classNames({
                                [classesEditorForm.hasError]: categoryError,
                            })}
                        />
                        <label>
                            <input
                                id={domTitleID}
                                className={classNames("inputText", classesEditorForm.title, {
                                    [classesEditorForm.hasError]: !!titleError,
                                })}
                                type="text"
                                placeholder={t("Title")}
                                aria-label={t("Title")}
                                value={props.form.name || ""}
                                onChange={titleChangeHandler}
                                disabled={isLoading}
                                aria-invalid={!!titleError}
                                aria-errormessage={titleError ? domTitleErrorsID : undefined}
                            />
                            {!!titleError && (
                                <AccessibleError
                                    id={domTitleErrorsID}
                                    error={titleError}
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
                        clearOperationsQueue={() => props.actions.clearEditorOps()}
                    >
                        <div className={classesEditorForm.embedBarContainer}>
                            <animated.div
                                className={classesEditorForm.embedBarTop}
                                style={{
                                    opacity: transition.headerBorderOpacity,
                                }}
                            />
                            <animated.div
                                style={{
                                    boxShadow: transition.embedBarBoxShadow,
                                }}
                            >
                                <EditorEmbedBar
                                    contentRef={embedBarRef}
                                    className={classNames(classesEditorForm.containerWidth)}
                                />
                            </animated.div>
                            <animated.div
                                className={classesEditorForm.embedBarBottom}
                                style={{
                                    opacity: transition.embedBarBorderOpacity,
                                }}
                            />
                            {bodyError && (
                                <div className={classNames(classesEditorForm.containerWidth)}>
                                    <div className={classesEditorForm.bodyErrorWrap}>
                                        <AccessibleError
                                            id={domEditorErrorID}
                                            ariaHidden={true}
                                            error={bodyError}
                                            className={classNames(classesEditorForm.bodyErrorMessage)}
                                            paragraphClassName={classesEditorForm.categoryErrorParagraph}
                                            wrapClassName={classesUserContent.root}
                                        />
                                    </div>
                                </div>
                            )}
                        </div>

                        {conversionNotice}
                        {translationNotice}
                        {articleRedirectionNotice}
                        <div
                            className={classNames(
                                "richEditor",
                                { isDisabled: isLoading },
                                "FormWrapper",
                                classesEditorForm.editor(contentSize.top),
                                { [classesEditorForm.hasError]: bodyError },
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
                                <EditorContent
                                    placeholderClassName={classesRichEditor.placeholder}
                                    placeholder={t("Type your article.")}
                                />
                                <EditorInlineMenus />
                                <EditorParagraphMenu />
                            </div>
                        </div>
                    </Editor>
                </main>
            </form>
        </TouchScrollable>
    );
}

/**
 * We need to throttle savinga draft.
 * We have this configured to only happen on the trailing edge of a 10 second interval.
 */
function useDraftThrottling(handler: () => void) {
    const throttledDraft = useCallback(
        throttle(handler, 10000, {
            leading: false,
            trailing: true,
        }),
        [handler],
    );
    return throttledDraft;
}

/**
 * Hook for the scroll transition in the editor page.
 *
 * The following should happen on scroll
 * - Header box shadow fades out.
 * - The short embed bar border fades out.
 * - A full header border/divider fades in.
 * - A full embed bar box shadow fades in.
 *
 * @param formRef A ref to the form element (the scroll container).
 * @param embedBarRef A ref to the embed bar (it's dimensions are needed).
 */
function useFormScrollTransition(
    formRef: React.RefObject<HTMLFormElement>,
    embedBarRef: React.RefObject<HTMLDivElement>,
) {
    const [scrollPos, setScrollPos] = useState(0);

    // Scroll handler to pass to the form element.
    const scrollHandler = useCallback(() => {
        requestAnimationFrame(() => {
            if (!formRef.current) {
                return;
            }
            setScrollPos(Math.max(0, formRef.current.scrollTop));
        });
    }, [setScrollPos, formRef.current]);

    // Calculate some dimensions.
    let start = 0;
    let end = 0;
    if (embedBarRef.current) {
        const rect = embedBarRef.current.getBoundingClientRect();
        start = rect.top / 2;
        end = rect.top + rect.height * 2;
    }
    const { y } = useSpring({
        y: Math.max(start, Math.min(end, scrollPos)),
        tension: 100,
    });

    // Fades in.
    const headerBorderOpacity = y.interpolate({
        range: [start, end],
        output: [0, 1],
    });

    // Fades out.
    const embedBarBorderOpacity = y.interpolate({
        range: [start, end],
        output: [1, 0],
    });

    const transparentShadow = shadowHelper().makeShadow(0);
    const fullShadow = shadowHelper().makeShadow(0.2);

    // Fades out.
    const headerBoxShadow = y.interpolate({
        range: [start, end],
        output: [fullShadow, transparentShadow],
    });

    // Fades in.
    const embedBarBoxShadow = y.interpolate({
        range: [start, end],
        output: [transparentShadow, fullShadow],
    });

    return {
        scrollHandler,
        headerBorderOpacity,
        headerBoxShadow,
        embedBarBorderOpacity,
        embedBarBoxShadow,
    };
}

interface IOwnProps extends RouteComponentProps<any> {
    titleID?: string;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState) {
    const {
        article,
        saveDraft,
        submit,
        form,
        formNeedsRefresh,
        editorOperationsQueue,
        notifyConversion,
        formErrors,
        fallbackLocale,
        notifyArticleRedirection,
    } = EditorPageModel.getStateSlice(state);

    return {
        article,
        saveDraft,
        submit,
        form,
        formNeedsRefresh,
        revision: EditorPageModel.selectActiveRevision(state),
        draft: EditorPageModel.selectDraft(state),
        editorOperationsQueue,
        notifyConversion,
        fallbackLocale,
        notifyArticleRedirection,
        formErrors,
    };
}

function mapDispatchToProps(dispatch) {
    const actions = new EditorPageActions(dispatch, apiv2);
    return { actions };
}

const withRedux = connect(mapStateToProps, mapDispatchToProps);

export default withRedux(withRouter(EditorForm));
