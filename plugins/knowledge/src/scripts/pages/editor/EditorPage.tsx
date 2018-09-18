/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import uniqueId from "lodash/uniqueId";
import Editor from "@rich-editor/components/editor/Editor";
import { t } from "@library/application";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout from "@knowledge/layouts/PanelLayout";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageHeading from "@knowledge/components/PageHeading";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { Devices } from "@knowledge/components/DeviceChecker";

interface IProps {
    device: Devices;
}

export class EditorPage extends React.Component<IProps> {
    public render() {
        const editorID = uniqueId();
        const editorDescriptionId = "editorDescription-" + editorID;

        return (
            <Container className="inheritHeight">
                <PanelLayout growMiddleBottom={true} device={this.props.device}>
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <PageHeading title={t("Write Discussion")} />
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidget>
                            <div className="FormWrapper inheritHeight">
                                <form className="inheritHeight">
                                    <div className="inputBlock">
                                        <input className="inputBlock-inputText inputText" type="text" />
                                    </div>
                                    <div className="richEditor inheritHeight">
                                        <Editor
                                            editorID={editorID}
                                            editorDescriptionID={editorDescriptionId}
                                            isPrimaryEditor={true}
                                            legacyMode={false}
                                        />
                                    </div>
                                </form>
                            </div>
                        </PanelWidget>
                    </PanelLayout.MiddleBottom>
                </PanelLayout>
            </Container>
        );
    }
}

export default withDevice<IProps>(EditorPage);
