/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import uniqueId from "lodash/uniqueId";
import { Editor } from "@rich-editor/components/editor/Editor";
import { t } from "@library/application";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout from "@knowledge/layouts/PanelLayout";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageHeading from "@knowledge/components/PageHeading";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { Devices } from "@knowledge/components/DeviceChecker";
import { DeltaOperation } from "quill/core";
import PageLocation from "@knowledge/components/locationPicker/PageLocation";

interface IProps {
    device: Devices;
    backUrl: string;
    submitHandler: (editorContent: DeltaOperation[], title: string) => void;
}

/**
 * Page layout for the Editor.
 */
export class EditorForm extends React.Component<IProps> {
    private editor: React.RefObject<Editor> = React.createRef();
    private title: React.RefObject<HTMLInputElement> = React.createRef();

    public render() {
        const dummyArticleLocationData = {
            children: [
                {
                    name: "Docs",
                    url: "/docs",
                },
                {
                    name: "Installation",
                    url: "/docs/installation",
                },
            ],
        };

        const editorID = uniqueId();
        const editorDescriptionId = "editorDescription-" + editorID;

        return (
            <Container className="inheritHeight">
                <PanelLayout growMiddleBottom={true} device={this.props.device}>
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <div className="sr-only">
                                <PageHeading backUrl={this.props.backUrl} title={t("Write Discussion")} />
                            </div>
                            <PageLocation {...dummyArticleLocationData} />
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidget>
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
                        </PanelWidget>
                    </PanelLayout.MiddleBottom>
                </PanelLayout>
            </Container>
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

export default withDevice<IProps>(EditorForm);
