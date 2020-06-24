/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { useGetThemeState } from "@library/theming/themeReducer";
import { useThemeActions } from "@library/theming/ThemeActions";
import { LoadStatus } from "@library/@types/api/core";
import { ThemeRevisionItem } from "@themingapi/theme/ThemeRevisionItem";
import classNames from "classnames";
import { themeRevisionPageClasses } from "@themingapi/theme/themeRevisionsPageStyles";
import { ThemePanelPlaceholder } from "@themingapi/theme/ThemePanelPlaceholder";
import { useThemeEditorState } from "@themingapi/theme/themeEditorReducer";

export interface IProps {
    themeID: number;
    selectedRevisionID: number;
    onSelectedRevisionIDChange: (id: number) => void;
}

export function ThemeRevisionsPanel(props: IProps) {
    const themeState = useGetThemeState();
    const actions = useThemeActions();
    const { formSubmit } = useThemeEditorState();
    const classes = themeBuilderClasses();
    const revisionPageClasses = themeRevisionPageClasses();

    const { themeID, selectedRevisionID, onSelectedRevisionIDChange } = props;
    const revisionStatus = themeState.themeRevisions.status;
    useEffect(() => {
        if (revisionStatus === LoadStatus.PENDING) {
            actions.getThemeRevisions(themeID);
        }
    }, [revisionStatus, formSubmit, themeID, actions]);

    const revisions = themeState.themeRevisions.data;

    if (
        themeState.themeRevisions.status === LoadStatus.LOADING ||
        themeState.themeRevisions.status === LoadStatus.ERROR
    ) {
        return <ThemePanelPlaceholder />;
    }

    const panelContent = revisions ? (
        revisions.map(revision => {
            let isSelected = revision.revisionID === selectedRevisionID;

            return (
                <ThemeRevisionItem
                    key={revision.revisionID}
                    revision={revision}
                    userInfo={revision.insertUser!}
                    isSelected={isSelected}
                    isActive={revision.active}
                    onClick={event => {
                        event.preventDefault();
                        onSelectedRevisionIDChange(revision.revisionID);
                    }}
                />
            );
        })
    ) : (
        <></>
    );

    return (
        <div className={classNames(classes.root)}>
            <div className={classes.section}>
                <h3 className={revisionPageClasses.title}>Revision History</h3>
                {panelContent}
            </div>
        </div>
    );
}
