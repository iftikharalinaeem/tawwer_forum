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

interface IProps {
    submitHandler: (editorContent: DeltaOperation[], title: string) => void;
}

/**
 * Form for the editor page.
 */
export default class EditorForm extends React.Component<IProps> {
    private editor: React.RefObject<Editor> = React.createRef();
    private title: React.RefObject<HTMLInputElement> = React.createRef();

    public render() {
        const editorID = uniqueId();
        const editorDescriptionId = "editorDescription-" + editorID;

        return (
            <div className="FormWrapper inheritHeight">
                <form className="inheritHeight" onSubmit={this.onSubmit}>
                    <div className="inputBlock">
                        <input
                            ref={this.title}
                            className="inputBlock-inputText inputText"
                            type="text"
                            placeholder={t("Title")}
                        />
                    </div>
                    <div className="richEditor inheritHeight">
                        <Editor
                            ref={this.editor}
                            editorID={editorID}
                            editorDescriptionID={editorDescriptionId}
                            isPrimaryEditor={true}
                            legacyMode={false}
                        />
                    </div>
                    <button type="submit">{t("Submit")}</button>
                </form>
            </div>
        );
    }

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        const content = this.editor.current!.getEditorContent()!;
        const title = this.title.current!.value;
        this.props.submitHandler(content, title);
    };
}
