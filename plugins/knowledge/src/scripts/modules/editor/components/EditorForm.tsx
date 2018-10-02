/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import uniqueId from "lodash/uniqueId";
import { Editor } from "@rich-editor/components/editor/Editor";
import { t } from "@library/application";
import { DeltaOperation } from "quill/core";
import { IArticleRevision } from "@knowledge/@types/api";
import { ILoadable, LoadStatus } from "@library/@types/api";
import Button from "@library/components/forms/Button";

interface IProps {
    submitHandler: (editorContent: DeltaOperation[], title: string) => void;
    revision: ILoadable<IArticleRevision>;
}

interface IState {
    name: string;
    body: DeltaOperation[];
}

/**
 * Form for the editor page.
 */
export default class EditorForm extends React.Component<IProps, IState> {
    public state = {
        name: "",
        body: [],
    };
    private editorRef: React.RefObject<Editor> = React.createRef();

    /**
     * @inheritdoc
     */
    public render() {
        const editorID = uniqueId();
        const editorDescriptionId = "editorDescription-" + editorID;

        return (
            <div className="FormWrapper inheritHeight">
                <form className="inheritHeight" onSubmit={this.onSubmit}>
                    <div className="inputBlock">
                        <input
                            className="inputBlock-inputText inputText"
                            type="text"
                            placeholder={t("Title")}
                            value={this.state.name}
                            onChange={this.titleChangeHandler}
                        />
                    </div>
                    <div className="richEditor inheritHeight">
                        <Editor
                            ref={this.editorRef}
                            editorID={editorID}
                            editorDescriptionID={editorDescriptionId}
                            isPrimaryEditor={true}
                            onChange={this.editorChangeHandler}
                            legacyMode={false}
                        />
                    </div>
                    <Button disabled={!this.canSubmit} type="submit">
                        {t("Submit")}
                    </Button>
                </form>
            </div>
        );
    }

    /**
     * Handle prop changes on the form.
     *
     * - If the revision changes from non successful to successful, initialize the form with values from that revision.
     *
     * @inheritdoc
     */
    public componentDidUpdate(oldProps: IProps) {
        const oldRevision = oldProps.revision;
        const revision = this.props.revision;
        if (oldRevision.status !== LoadStatus.SUCCESS && revision.status === LoadStatus.SUCCESS) {
            this.initFormFromRevision(revision.data);
        }
    }

    private get canSubmit(): boolean {
        if (!this.editorRef.current) {
            return false;
        }
        const minTitleLength = 1;
        const minBodyLength = 1;

        const title = this.state.name;
        const body = this.editorRef.current.getEditorText();

        return title.length >= minTitleLength && body.length >= minBodyLength;
    }

    /**
     * Use a revision to initialize form values.
     *
     * @param revision - The revision to pull data from.
     */
    private initFormFromRevision(revision: IArticleRevision) {
        this.editorRef.current!.setEditorContent(JSON.parse(revision.body));
        this.setState({ name: revision.name });
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
