/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Editor } from "@rich-editor/components/editor/Editor";
import { t } from "@library/application";
import { DeltaOperation } from "quill/core";
import { IArticleRevision, IKbCategoryFragment } from "@knowledge/@types/api";
import { ILoadable, LoadStatus } from "@library/@types/api";
import Button from "@library/components/forms/Button";
import LocationInput from "@knowledge/modules/locationPicker/LocationInput";
import DocumentTitle from "@library/components/DocumentTitle";

interface IProps {
    submitHandler: (editorContent: DeltaOperation[], title: string) => void;
    revision: ILoadable<IArticleRevision | undefined>;
    articleCategory: IKbCategoryFragment;
}

interface IState {
    name: string;
    body: DeltaOperation[];
}

/**
 * Form for the editor page.
 */
export default class EditorForm extends React.Component<IProps, IState> {
    private editorRef: React.RefObject<Editor> = React.createRef();

    public constructor(props: IProps) {
        super(props);

        if (this.props.revision.status === LoadStatus.SUCCESS && this.props.revision.data) {
            this.state = {
                name: this.props.revision.data.name,
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
        if (this.props.revision.status === LoadStatus.SUCCESS && this.props.revision.data) {
            this.editorRef.current!.setEditorContent(JSON.parse(this.props.revision.data.body));
        }
    }

    /**
     * @inheritdoc
     */
    public render() {
        const isLoadingOrPending = [LoadStatus.LOADING, LoadStatus.PENDING].includes(this.props.revision.status);
        return (
            <div className="FormWrapper inheritHeight">
                <form className="inheritHeight" onSubmit={this.onSubmit}>
                    <LocationInput initialCategoryID={1} />
                    <div className="inputBlock">
                        <DocumentTitle title={isLoadingOrPending ? "Loading" : this.state.name || t("Untitled")}>
                            <input
                                className="inputBlock-inputText inputText"
                                type="text"
                                placeholder={t("Title")}
                                value={this.state.name}
                                onChange={this.titleChangeHandler}
                                disabled={this.isLoading}
                            />
                        </DocumentTitle>
                    </div>
                    <Editor
                        allowUpload={true}
                        ref={this.editorRef}
                        isPrimaryEditor={true}
                        onChange={this.editorChangeHandler}
                        className="inheritHeight"
                        isLoading={this.isLoading}
                    />
                    <Button disabled={!this.canSubmit} type="submit">
                        {t("Submit")}
                    </Button>
                </form>
            </div>
        );
    }

    private get isLoading(): boolean {
        return this.props.revision.status === LoadStatus.LOADING;
    }

    private get canSubmit(): boolean {
        if (!this.editorRef.current) {
            return false;
        }
        const minTitleLength = 1;
        const minBodyLength = 1;

        const title = this.state.name;
        const body = this.editorRef.current.getEditorText().trim();

        return title.length >= minTitleLength && body.length >= minBodyLength;
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
