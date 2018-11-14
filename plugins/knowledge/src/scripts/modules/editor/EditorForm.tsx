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
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import Container from "@knowledge/layouts/components/Container";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import EditorPageModel, { IInjectableEditorProps, IEditorPageForm } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorMenu from "@knowledge/modules/editor/components/EditorMenu";
import { connect } from "react-redux";
import apiv2 from "@library/apiv2";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";
import { RouteComponentProps, withRouter } from "react-router-dom";

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

    private ignoreEditorUpdates = false;

    public state: IState = {
        isDirty: false,
    };

    /**
     * @inheritdoc
     */
    public render() {
        const { article, form } = this.props;
        return (
            <form className="richEditorForm inheritHeight" onSubmit={this.onSubmit}>
                <EditorHeader
                    canSubmit={this.canSubmit}
                    isSubmitLoading={this.props.submit.status === LoadStatus.LOADING}
                    className="richEditorForm-header"
                    savedDraft={this.draft}
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
                                />
                            </div>
                        </PanelLayout.MiddleBottom>
                    </PanelLayout>
                </Container>
            </form>
        );
    }

    public componentDidMount() {
        this.overrideEditorContents();
    }

    public componentDidUpdate(prevProps: IProps) {
        // Force override the editor contents if we change from loading to not loading.
        if (this.propsAreLoading(prevProps) && !this.propsAreLoading(this.props)) {
            this.overrideEditorContents();
        }
    }

    private get isLoading(): boolean {
        return this.propsAreLoading(this.props);
    }

    private get draft(): ILoadable<IResponseArticleDraft> {
        if (this.props.savedDraft.data || this.props.initialDraft.status === LoadStatus.LOADING) {
            return this.props.savedDraft;
        } else {
            return this.props.initialDraft;
        }
    }

    private propsAreLoading(props: IProps): boolean {
        const { article, revision, initialDraft } = props;
        return [article.status, revision.status, initialDraft.status].includes(LoadStatus.LOADING);
    }

    private overrideEditorContents() {
        this.ignoreEditorUpdates = true;
        this.editorRef.current!.setEditorContent(this.props.form.body);
        this.ignoreEditorUpdates = false;
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

    private handleFormChange(form: Partial<IEditorPageForm>) {
        if (this.ignoreEditorUpdates) {
            return;
        }
        this.props.actions.updateForm(form);
        this.updateDraft();
    }

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

    private updateDraft = throttle(
        () => {
            this.setState({ isDirty: false });
            this.props.actions.syncDraft();
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
        this.props.actions.publish(this.props.history);
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
