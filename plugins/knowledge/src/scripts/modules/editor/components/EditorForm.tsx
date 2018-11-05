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
import PanelLayout from "@knowledge/layouts/PanelLayout";
import Container from "@knowledge/layouts/components/Container";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import EditorMenu from "./EditorMenu";

type LoadableContent = ILoadable<{
    name: string;
    body: string;
}>;

interface IProps {
    device: Devices;
    submitHandler: (editorContent: DeltaOperation[], title: string) => void;
    article: ILoadable<IArticle>;
    content: LoadableContent;
    currentCategory: IKbCategoryFragment | null;
    className?: string;
    isSubmitLoading: boolean;
    titleID?: string;
}

interface IState {
    name: string;
    body: DeltaOperation[];
}

/**
 * Form for the editor page.
 */
export class EditorForm extends React.Component<IProps, IState> {
    private editorRef: React.RefObject<Editor> = React.createRef();

    public constructor(props: IProps) {
        super(props);
        if (this.props.content.status === LoadStatus.SUCCESS && this.props.content.data) {
            this.state = {
                name: this.props.content.data.name,
                body: [],
            };
        } else {
            this.state = {
                name: "",
                body: [],
            };
        }
    }

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
        const categoryID = this.props.currentCategory !== null ? this.props.currentCategory.knowledgeCategoryID : null;
        const { article } = this.props;
        return (
            <form className="richEditorForm inheritHeight" onSubmit={this.onSubmit}>
                <EditorHeader
                    canSubmit={this.canSubmit}
                    isSubmitLoading={this.props.isSubmitLoading}
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
                                    initialCategoryID={categoryID}
                                    key={categoryID === null ? undefined : categoryID}
                                />
                                <div className="sr-only">
                                    <DocumentTitle title={this.state.name || "Untitled"} />
                                </div>
                                <input
                                    className="richEditorForm-title inputBlock-inputText inputText"
                                    type="text"
                                    placeholder={t("Title")}
                                    value={this.state.name || ""}
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
                                />
                            </div>
                        </PanelLayout.MiddleBottom>
                    </PanelLayout>
                </Container>
            </form>
        );
    }

    private get isLoading(): boolean {
        return this.props.content.status === LoadStatus.LOADING;
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

        const title = this.state.name || "";
        const body = this.editorRef.current.getEditorText().trim();

        return title.length >= minTitleLength && body.length >= minBodyLength && this.props.currentCategory !== null;
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

export default withDevice<IProps>(EditorForm);
