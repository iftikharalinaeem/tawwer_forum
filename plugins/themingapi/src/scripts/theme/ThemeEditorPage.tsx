/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useCallback } from "react";
import { BrowserRouter, withRouter } from "react-router-dom";
import { ActionBar } from "@library/headers/ActionBar";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";

import { DataTabs } from "@library/sectioning/Tabs";
import TextEditor from "@library/textEditor/TextEditor";
import apiv2 from "@library/apiv2";
import { connect } from "react-redux";

interface IProps {}

export default function ThemeEditorPage(props: IProps) {
    /*const onSubmit = useCallback(
        (event: React.FormEvent) => {
            event.preventDefault();
            event.stopPropagation();
            void props.actions.publish(props.history);
        },
        [props.actions.publish, props.history, pushSmartLocation],
    );*/
    const tabData = [
        { label: "Header", panelData: "header" },
        { label: "Footer", panelData: "footer" },
        { label: "CSS", panelData: "css" },
        { label: "JS", panelData: "js" },
    ];
    return (
        <BrowserRouter>
            <React.Fragment>
                <form>
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
                </form>
            </React.Fragment>
        </BrowserRouter>
    );
} /*
function mapDispatchToProps(dispatch) {
    const actions = new ThemeEditorPageActions(dispatch, apiv2);
    return { actions };
}

const withRedux = connect(mapDispatchToProps);

export default withRedux(withRouter(ThemeEditorPage));
*/
