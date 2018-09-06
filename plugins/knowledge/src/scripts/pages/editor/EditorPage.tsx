/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import uniqueId from "lodash/uniqueId";
import Editor from "@rich-editor/components/editor/Editor";

export default class HomePage extends React.Component {
    public render() {
        const editorID = uniqueId();
        const editorDescriptionId = "editorDescription-" + editorID;

        return (
            <div>
                Hello Editor
                {/* TODO: Remove the need for these wrappers. Currently the focus module depends on them. */}
                <div className="FormWrapper">
                    <div className="richEditor">
                        <Editor editorID={editorID} editorDescriptionID={editorDescriptionId} isPrimaryEditor={true} />
                    </div>
                </div>
            </div>
        );
    }
}
