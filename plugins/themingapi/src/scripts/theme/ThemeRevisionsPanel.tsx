/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { useGetThemeState } from "@library/theming/themeReducer";
import { useThemeActions } from "@library/theming/ThemeActions";
import { LoadStatus } from "@library/@types/api/core";
import { ThemeRevisionItem } from "@themingapi/theme/ThemeRevisionItem";
import classNames from "classnames";
import { themeRevisionPageClasses } from "@themingapi/theme/themeRevisionsPageStyles";
import { ThemePanelPlaceholder } from "@themingapi/theme/ThemePanelPlaceholder";

export interface IProps {
    themeID: number;
    handleChange: (id: any) => void;
    disabled: boolean;
}

export function ThemeRevisionsPanel(props: IProps) {
    const themeState = useGetThemeState();
    const actions = useThemeActions();
    const [revisions, setRevisions] = useState();
    const [selectedRevisionID, setSelectedRevisionID] = useState();
    const classes = themeBuilderClasses();
    const revisionPageClasses = themeRevisionPageClasses();

    useEffect(() => {
        if (themeState.themeRevisions.status === LoadStatus.PENDING) {
            actions.getThemeRevisions(props.themeID);
        }
    }, [themeState]);

    useEffect(() => {
        setRevisions(themeState.themeRevisions.data);
        const initialActiveRevision = revisions
            ? revisions.find(revision => {
                  return revision.active === true;
              })
            : undefined;
        if (initialActiveRevision) {
            setSelectedRevisionID(initialActiveRevision.revisionID);
        }
    }, [themeState, revisions]);

    useEffect(() => {
        props.handleChange(selectedRevisionID);
    }, [selectedRevisionID]);

    if (
        themeState.themeRevisions.status === LoadStatus.LOADING ||
        themeState.themeRevisions.status === LoadStatus.ERROR
    ) {
        return <ThemePanelPlaceholder />;
    }

    const panelContent = revisions ? (
        revisions.map(revision => {
            let isSelected = false;
            if (revision.revisionID === selectedRevisionID) {
                isSelected = true;
            }

            return (
                <ThemeRevisionItem
                    key={revision.revisionID}
                    revision={revision}
                    userInfo={revision.insertUser}
                    isSelected={isSelected}
                    isActive={revision.active}
                    onClick={event => {
                        event.preventDefault();
                        setSelectedRevisionID(revision.revisionID);
                    }}
                    disabled={props.disabled}
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
