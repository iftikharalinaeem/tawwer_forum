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
import { NavigationPlaceholder } from "@knowledge/navigation/NavigationPlaceholder";
import { ThemeRevisionItem } from "@library/forms/themeEditor/ThemeRevisionItem";

export interface IProps {
    themeID: number;
    handleChange: (id: any) => void;
}

export function ThemeRevisionsPanel(props: IProps) {
    const themeState = useGetThemeState();
    const actions = useThemeActions();
    const [revisions, setRevisions] = useState();
    const [selectedRevisionID, setSelectedRevisionID] = useState();
    const classes = themeBuilderClasses();

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
        return <NavigationPlaceholder />;
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
                    name={revision.insertUser?.name}
                    imageUrl={revision.insertUser?.photoUrl}
                    date={revision.dateInserted}
                    userInfo={revision.insertUser}
                    revisionID={revision.revisionID}
                    isSelected={isSelected}
                    onClick={event => {
                        event.preventDefault();
                        setSelectedRevisionID(revision.revisionID);
                    }}
                    isLoading={themeState.themeRevisions.status === LoadStatus.LOADING}
                />
            );
        })
    ) : (
        <></>
    );

    return (
        <div className={classes.root}>
            <ThemeBuilderSectionGroup label={"Revisions"}>{panelContent}</ThemeBuilderSectionGroup>
        </div>
    );
}
