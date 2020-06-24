/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { ActionBar } from "@library/headers/ActionBar";
import { useIFrameCommunication } from "@themingapi/theme/IframeCommunicationContext";
import { ThemeEditorTitle } from "@themingapi/theme/ThemeEditorTitle";
import { t } from "@vanilla/i18n";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import { useFallbackBackUrl } from "@vanilla/library/src/scripts/routing/links/BackRoutingProvider";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";
import React, { useEffect, useState } from "react";
import { useHistory, useParams } from "react-router-dom";
import { useThemeEditorActions } from "./ThemeEditorActions";
import { useThemeEditorState } from "./themeEditorReducer";
import { siteUrl, formatUrl } from "@library/utility/appUtils";
import { ThemeRevisionsPanel } from "@themingapi/theme/ThemeRevisionsPanel";
import { themeEditorClasses } from "@themingapi/theme/ThemeEditor.styles";
import { PreviewStatusType, useThemeActions } from "@library/theming/ThemeActions";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { themeRevisionPageClasses } from "@themingapi/theme/themeRevisionsPageStyles";

export default function ThemeRevisionsPage() {
    const titleID = useUniqueID("themeEditor");
    const { getThemeById } = useThemeEditorActions();
    const { putPreviewTheme, patchThemeWithRevisionID } = useThemeActions();
    const { previewStatus } = useThemeSettingsState();
    const { theme, formSubmit } = useThemeEditorState();
    const [revisionID, setRevisionID] = useState();
    const classes = themeEditorClasses();
    const RevisionPageClasses = themeRevisionPageClasses();
    const { setIFrameRef } = useIFrameCommunication();

    useFallbackBackUrl("/theme/theme-settings");

    const themeStatus = theme.status;
    const formStatus = formSubmit.status;
    const history = useHistory();

    let themeID = useParams<{
        id: string;
    }>().id;

    useEffect(() => {
        if (themeStatus === LoadStatus.PENDING && themeID !== undefined) {
            getThemeById(themeID, history);
        }
    }, [themeStatus, themeID, getThemeById, history]);

    useEffect(() => {
        if (theme.status === LoadStatus.SUCCESS && theme.data) {
            setRevisionID(theme.data.revisionID);
        }
    }, [theme.status, theme.data]);

    const submitHandler = async event => {
        event.preventDefault();
        if (revisionID !== null && themeID) {
            const theme = await patchThemeWithRevisionID({ themeID: themeID, revisionID: revisionID });
        }
    };

    const handleChange = id => {
        setRevisionID(id);
    };

    const handlePreview = async () => {
        putPreviewTheme({ themeID: themeID, revisionID: revisionID, type: PreviewStatusType.PREVIEW });
    };

    useEffect(() => {
        if (previewStatus.status === LoadStatus.SUCCESS) {
            window.location.href = formatUrl("/", true);
        }
    });

    let content: React.ReactNode;
    if (theme.status === LoadStatus.LOADING || theme.status === LoadStatus.PENDING) {
        content = <Loader />;
    } else if (theme.status === LoadStatus.ERROR || !theme.data || isNaN(parseInt(themeID))) {
        content = <ErrorPage error={theme.error} />;
    } else {
        const contents = (
            <div>
                <div className={classes.wrapper}>
                    <div className={RevisionPageClasses.frame}>
                        <iframe
                            ref={setIFrameRef}
                            src={siteUrl(`/theme/theme-settings/${themeID}/preview?revisionID=${revisionID}`)}
                            width="100%"
                            height="100%"
                            scrolling="yes"
                        ></iframe>
                        <div className={classes.shadowTop}></div>
                        <div className={classes.shadowRight}></div>
                    </div>

                    <div className={classes.panel}>
                        <ThemeRevisionsPanel
                            themeID={parseInt(themeID)}
                            selectedRevisionID={revisionID}
                            onSelectedRevisionIDChange={handleChange}
                        />
                    </div>
                </div>
            </div>
        );
        content = (
            <>
                <form onSubmit={submitHandler}>
                    <ActionBar
                        useShadow={false}
                        callToActionTitle={t("Restore")}
                        anotherCallToActionTitle={"Preview"}
                        title={<ThemeEditorTitle themeName={theme.data?.name} isDisabled={true} />}
                        fullWidth={true}
                        backTitle={t("Back")}
                        isCallToActionLoading={formStatus === LoadStatus.LOADING}
                        isCallToActionDisabled={false}
                        anotherCallToActionLoading={previewStatus.status === LoadStatus.LOADING}
                        anotherCallToActionDisabled={false}
                        handleAnotherSubmit={handlePreview}
                    />
                </form>
                {contents}
            </>
        );
    }

    return (
        <Modal isVisible={true} scrollable={true} titleID={titleID} size={ModalSizes.FULL_SCREEN}>
            {content}
        </Modal>
    );
}
