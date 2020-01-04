/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { BrowserRouter } from "react-router-dom";
import { ActionBar } from "@library/headers/ActionBar";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";

import { DataTabs } from "@library/sectioning/Tabs";
import TextEditor from "@library/textEditor/TextEditor";

interface IProps {}

interface IState {}

export default class ThemeEditorPage extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
    }
    public render() {
        const tabData = [
            { label: "Header", panelData: "header" },
            { label: "Footer", panelData: "footer" },
            { label: "CSS", panelData: "css" },
            { label: "JS", panelData: "js" },
        ];
        return (
            <BrowserRouter>
                <React.Fragment>
                    <DashboardHeaderBlock title={t("Theme Editor")} />
                    <ActionBar
                        callToActionTitle={"Save"}
                        optionsMenu={
                            <DropDown flyoutType={FlyoutType.LIST}>
                                <DropDownItemButton onClick={() => {}}>someItem</DropDownItemButton>
                            </DropDown>
                        }
                    />
                    <DataTabs data={tabData}>
                        <TextEditor
                            height={"90vh"} // By default, it fully fits with its parent
                            theme={"dark"}
                            language={"html"}
                            // editorDidMount={handleEditorDidMount}
                            options={{ lineNumbers: "on" }}
                        />
                    </DataTabs>
                </React.Fragment>
            </BrowserRouter>
        );
    }
}
