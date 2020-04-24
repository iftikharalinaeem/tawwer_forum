/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { ThemeBuilderSectionGroup } from "@library/forms/themeEditor/ThemeBuilderSectionGroup";
import { useGetThemeState } from "@library/theming/themeReducer";
import { useThemeActions } from "@library/theming/ThemeActions";
import { LoadStatus } from "@library/@types/api/core";

export interface IProps {
    themeID: number;
}

export function ThemeRevisionsPanel(props: IProps) {
    const themeState = useGetThemeState();
    const actions = useThemeActions();

    useEffect(() => {
        if (themeState.themeRevisions.status === LoadStatus.PENDING) {
            actions.getThemeRevisions(props.themeID);
        }
    }, [themeState]);

    const revisions = themeState.themeRevisions?.data;
    console.log(revisions);

    const classes = themeBuilderClasses();
    return (
        <>
            <div className={classes.root}>
                <ThemeBuilderSectionGroup label={"Revisions"}></ThemeBuilderSectionGroup>
            </div>
        </>
    );
}
