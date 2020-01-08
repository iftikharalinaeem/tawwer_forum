/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { BrowserRouter } from "react-router-dom";
import { ActionBar } from "@library/headers/ActionBar";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { DataTabs } from "@library/sectioning/Tabs";
import TextEditor from "@library/textEditor/TextEditor";
import { useThemeActions } from "./ThemeActions";
import { useThemeEditorState } from "./themeEditorReducer";

interface IProps {}
export default function ThemeEditorPage(props: IProps) {
    const { updateAssets, saveTheme, initAssets, getTheme } = useThemeActions();
    const { theme } = useThemeEditorState();
    const [header, setHeader] = useState("");
    const [footer, setFooter] = useState("");
    const [js, setJS] = useState("");
    const [css, setCss] = useState("");

    const tabData = [
        {
            label: "Header",
            panelData: "header",
            contents: (
                <TextEditor
                    language={"html"}
                    value={""}
                    onChange={(event, newValue) => {
                        updateAssets({ header: newValue });
                        setHeader(newValue ? newValue : "");
                    }}
                />
            ),
        },

        {
            label: "Footer",
            panelData: "footer",
            contents: (
                <TextEditor
                    language={"html"}
                    value={""}
                    onChange={(event, newValue) => {
                        updateAssets({ footer: newValue });
                        setFooter(newValue ? newValue : "");
                    }}
                />
            ),
        },
        {
            label: "CSS",
            panelData: "css",
            contents: (
                <TextEditor
                    language={"css"}
                    value={""}
                    onChange={(event, newValue) => {
                        updateAssets({ style: newValue });
                        setCss(newValue ? newValue : "");
                    }}
                />
            ),
        },
        {
            label: "JS",
            panelData: "js",
            contents: (
                <TextEditor
                    language={"javascript"}
                    value={""}
                    onChange={(event, newValue) => {
                        updateAssets({ javascript: newValue });
                        setJS(newValue ? newValue : "");
                    }}
                />
            ),
        },
    ];

    const testAssets = {
        header: header,
        footer: footer,
    };
    return (
        <BrowserRouter>
            <React.Fragment>
                <form
                    onSubmit={async event => {
                        event.preventDefault();
                        void saveTheme(testAssets, 1);
                    }}
                >
                    <ActionBar
                        callToActionTitle={"Save"}
                        optionsMenu={
                            <DropDown flyoutType={FlyoutType.LIST}>
                                <DropDownItemButton onClick={() => {}}>someItem</DropDownItemButton>
                            </DropDown>
                        }
                    />
                    <DataTabs data={tabData} />
                </form>
            </React.Fragment>
        </BrowserRouter>
    );
}
