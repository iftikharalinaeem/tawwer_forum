/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Editor } from "@rich-editor/components/editor/Editor";
import { t } from "@library/application";
import { DeltaOperation } from "quill/core";
import { IKbCategoryFragment, IArticle } from "@knowledge/@types/api";
import { ILoadable, LoadStatus } from "@library/@types/api";
import LocationInput from "@knowledge/modules/locationPicker/LocationInput";
import DocumentTitle from "@library/components/DocumentTitle";
import classNames from "classnames";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import Container from "@knowledge/layouts/components/Container";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import EditorPageModel, { IInjectableEditorProps } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorMenu from "@knowledge/modules/editor/components/EditorMenu";
import { connect } from "react-redux";
import apiv2 from "@library/apiv2";

interface IProps extends IInjectableEditorProps, IDeviceProps {
    actions: EditorPageActions;
    className?: string;
    titleID?: string;
}

/**
 * Form for the editor page.
 */
export class EditorForm extends React.Component<IProps> {
    private editorRef: React.RefObject<Editor> = React.createRef();

    public componentDidMount() {
        if (this.props.content.status === LoadStatus.SUCCESS && this.props.content.data) {
            if (this.props.content.data.body) {
                this.editorRef.current!.setEditorContent(JSON.parse(this.props.content.data.body));
            }
        }
    }

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
                                    key={form.knowledgeCategoryID === null ? undefined : form.knowledgeCategoryID}
                                    disabled={this.isLoading}
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
                                    legacyMode={true}
                                />
                            </div>
                        </PanelLayout.MiddleBottom>
                    </PanelLayout>
                </Container>
            </form>
        );
    }

    private get isLoading(): boolean {
        const { article, revision, draft } = this.props;
        return [article.status, revision.status, draft.status].includes[LoadStatus.LOADING];
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
     * Change handler for the editor.
     */
    private editorChangeHandler = (content: DeltaOperation[]) => {
        this.setState({ body: content });
    };

    /**
     * Change handler for the title
     */
    private titleChangeHandler = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.setState({ name: event.target.value });
    };

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        this.props.submitHandler(this.state.body, this.state.name);
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

export default withRedux(withDevice<IProps>(EditorForm));
