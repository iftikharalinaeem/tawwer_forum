/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { ActionBar } from "@library/headers/ActionBar";
import {
    IframeCommunicationContextProvider,
    useIFrameCommunication,
} from "@themingapi/theme/IframeCommunicationContext";
import { ThemeEditorTitle } from "@themingapi/theme/ThemeEditorTitle";
import { t } from "@vanilla/i18n";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import { useFallbackBackUrl } from "@vanilla/library/src/scripts/routing/links/BackRoutingProvider";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";
import { useLastValue } from "@vanilla/react-utils";
import React, { useEffect, useState } from "react";
import { RouteComponentProps, useHistory } from "react-router-dom";
import { useThemeEditorActions } from "./ThemeEditorActions";
import { useThemeEditorState } from "./themeEditorReducer";
import { IThemeAssets } from "@vanilla/library/src/scripts/theming/themeReducer";
import { bodyCSS } from "@vanilla/library/src/scripts/layout/bodyStyles";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { formatUrl, siteUrl } from "@library/utility/appUtils";
import { ThemeRevisionsPanel } from "@themingapi/theme/ThemeRevisionsPanel";
import { themeEditorClasses } from "@themingapi/theme/ThemeEditor.styles";
import { PreviewStatusType, useThemeActions } from "@library/theming/ThemeActions";
import { tabBrowseClasses } from "@library/sectioning/tabStyles";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { makeThemeEditorUrl } from "@themingapi/routes/makeThemeEditorUrl";
import { themeRevisionPageClasses } from "@themingapi/theme/themeRevisionsPageStyles";

interface IProps extends IOwnProps {
    themeID: number;
    type?: string;
    name?: string;
    assets?: IThemeAssets;
}
interface IOwnProps
    extends RouteComponentProps<{
        id: string;
    }> {}

export default function ThemeRevisionsPage(this: any, props: IProps, ownProps: IOwnProps) {
    const titleID = useUniqueID("themeEditor");
    const { patchThemeWithRevisionID, getThemeById } = useThemeEditorActions();
    const { putPreviewTheme } = useThemeActions();
    const { previewStatus } = useThemeSettingsState();
    const { theme } = useThemeEditorState();
    const [revisionID, setRevisionID] = useState();
    const [iframeLoading, setIframeLoading] = useState(true);
    const [isFormSubmitting, setIsFormSubmitting] = useState(false);
    const { pushSmartLocation } = useLinkContext();
    const classes = themeEditorClasses();
    const RevisionPageClasses = themeRevisionPageClasses();

    const { setIFrameRef } = useIFrameCommunication();

    let themeID = props.match.params.id;

    useFallbackBackUrl("/theme/theme-settings");

    const themeStatus = theme.status;
    const history = useHistory();

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
            setIsFormSubmitting(true);
            const updatedTheme = await patchThemeWithRevisionID({ themeID: themeID, revisionID: revisionID });
            if (updatedTheme) {
                setIsFormSubmitting(false);
            }
        }
    };

    const handleChange = id => {
        setRevisionID(id);
        setIframeLoading(true);
    };

    const handlePreview = async () => {
        putPreviewTheme({ themeID: themeID, revisionID: revisionID, type: PreviewStatusType.PREVIEW });
    };

    useEffect(() => {
        if (previewStatus.status === LoadStatus.SUCCESS) {
            window.location.href = "/";
        }
    });

    const handleReload = e => {
        setIframeLoading(false);
    };

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
                            onLoad={handleReload}
                        ></iframe>
                        <div className={classes.shadowTop}></div>
                        <div className={classes.shadowRight}></div>
                    </div>

                    <div className={classes.panel}>
                        <ThemeRevisionsPanel
                            themeID={parseInt(themeID)}
                            handleChange={handleChange}
                            disabled={iframeLoading}
                            updated={isFormSubmitting}
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
                        callToActionTitle={isFormSubmitting ? t("Restored") : t("Restore")}
                        anotherCallToActionTitle={"Preview"}
                        title={<ThemeEditorTitle themeName={theme.data?.name} isDisabled={true} />}
                        fullWidth={true}
                        backTitle={t("Back")}
                        isCallToActionLoading={isFormSubmitting}
                        isCallToActionDisabled={iframeLoading}
                        anotherCallToActionLoading={false}
                        anotherCallToActionDisabled={iframeLoading}
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
