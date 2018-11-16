/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Editor } from "@rich-editor/components/editor/Editor";
import { t } from "@library/application";
import { DeltaOperation } from "quill/core";
import { IKbCategoryFragment, IArticle, IResponseArticleDraft } from "@knowledge/@types/api";
import { ILoadable, LoadStatus } from "@library/@types/api";
import LocationInput from "@knowledge/modules/locationPicker/LocationInput";
import DocumentTitle from "@library/components/DocumentTitle";
import classNames from "classnames";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import EditorPageModel, { IInjectableEditorProps, IEditorPageForm } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorMenu from "@knowledge/modules/editor/components/EditorMenu";
import { connect } from "react-redux";
import apiv2 from "@library/apiv2";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";
import { RouteComponentProps, withRouter } from "react-router-dom";
import Container from "@library/components/layouts/components/Container";
import PanelLayout from "@library/components/layouts/PanelLayout";
import { withDevice } from "@library/contexts/DeviceContext";
import { uniqueId } from "lodash";

interface IProps extends IInjectableEditorProps, IDeviceProps, RouteComponentProps<any> {
    actions: EditorPageActions;
    className?: string;
    titleID?: string;
}

interface IState {
    isDirty: boolean;
}

/**
 * Form for the editor page.
 */
export class EditorForm extends React.PureComponent<IProps, IState> {
    private editorRef: React.RefObject<Editor> = React.createRef();

    public state: IState = {
        isDirty: false,
    };

    /**
     * @inheritdoc
     */
    public render() {
        const { article, draft, form, formNeedsRefresh } = this.props;
        const debug = draft.status;
        return (
            <form className="richEditorForm inheritHeight" onSubmit={this.onSubmit}>
                <EditorHeader
                    canSubmit={this.canSubmit}
                    isSubmitLoading={this.props.submit.status === LoadStatus.LOADING}
                    className="richEditorForm-header"
                    draft={draft}
                    optionsMenu={
                        article.status === LoadStatus.SUCCESS && article.data ? (
                            <EditorMenu article={article.data} />
                        ) : null
                    }
                />
                <Container className="richEditorForm-body">
                    <h1 id={this.props.titleID} className="sr-only">
                        {t("Write Discussion")}
                    </h1>
                    <PanelLayout className="isOneCol" growMiddleBottom={true} device={this.props.device}>
                        <PanelLayout.MiddleBottom>
                            <div className={classNames("richEditorForm", "inheritHeight", this.props.className)}>
                                <LocationInput
                                    initialCategoryID={form.knowledgeCategoryID}
                                    key={form.knowledgeCategoryID || undefined}
                                    disabled={this.isLoading}
                                    onChange={this.locationPickerChangeHandler}
                                />
                                <div className="sr-only">
                                    <DocumentTitle title={this.props.form.name || "Untitled"} />
                                </div>
                                <input
                                    className="richEditorForm-title inputBlock-inputText inputText"
                                    type="text"
                                    placeholder={t("Title")}
                                    value={this.props.form.name || ""}
                                    onChange={this.titleChangeHandler}
                                    disabled={this.isLoading}
                                />
                                <Editor
                                    allowUpload={true}
                                    ref={this.editorRef}
                                    isPrimaryEditor={true}
                                    onChange={this.editorChangeHandler}
                                    className="FormWrapper inheritHeight richEditorForm-editor"
                                    isLoading={this.isLoading}
                                    device={this.props.device}
                                    legacyMode={false}
                                    key={formNeedsRefresh ? "1" : "0"}
                                    initialValue={form.body}
                                />
                            </div>
                        </PanelLayout.MiddleBottom>
                    </PanelLayout>
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
     * Whether or not we have all of the data we need to submit the form.
     */
    private get canSubmit(): boolean {
        if (!this.editorRef.current) {
            return false;
        }
        const minTitleLength = 1;
        const minBodyLength = 1;

        const title = this.props.form.name || "";
        const body = this.editorRef.current.getEditorText().trim();

        return (
            title.length >= minTitleLength &&
            body.length >= minBodyLength &&
            this.props.form.knowledgeCategoryID !== null
        );
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
    private locationPickerChangeHandler = (categoryID: number) => {
        this.handleFormChange({ knowledgeCategoryID: categoryID });
    };

    /**
     * Change handler for the editor.
     */
    private editorChangeHandler = debounce((content: DeltaOperation[]) => {
        this.handleFormChange({ body: content });
        this.updateDraft();
    }, 1000 / 60);

    /**
     * Update the draft from the contents of the form.
     *
     * This it throttled to happen at most and every 10 seconds.
     */
    private updateDraft = throttle(
        () => {
            this.setState({ isDirty: false });
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
        void this.props.actions.publish(this.props.history);
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    return {
        actions: new EditorPageActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    EditorPageModel.getInjectableProps,
    mapDispatchToProps,
);

export default withRedux(withRouter(withDevice<IProps>(EditorForm)));
