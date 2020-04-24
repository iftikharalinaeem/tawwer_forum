/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { ActionBar } from "@library/headers/ActionBar";
import { Tabs } from "@library/sectioning/Tabs";
import TextEditor, { TextEditorContextProvider } from "@library/textEditor/TextEditor";
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
import qs from "qs";
import React, { useEffect, useState } from "react";
import { RouteComponentProps, useHistory } from "react-router-dom";
import ThemeEditor from "./ThemeEditor";
import { useThemeEditorActions } from "./ThemeEditorActions";
import { useThemeEditorState } from "./themeEditorReducer";
import { IThemeAssets } from "@vanilla/library/src/scripts/theming/themeReducer";
import { bodyCSS } from "@vanilla/library/src/scripts/layout/bodyStyles";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { siteUrl } from "@library/utility/appUtils";
import { ThemeRevisionsPanel } from "@themingapi/theme/ThemeRevisionsPanel";
import ThemeBuilderForm from "@themingapi/theme/ThemeBuilderPanel";
import { themeEditorClasses } from "@themingapi/theme/ThemeEditor.styles";
import { PreviewStatusType, useThemeActions } from "@library/theming/ThemeActions";

interface IProps extends IOwnProps {
    themeID: string | number;
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
    const { patchThemeWithRevisionID } = useThemeEditorActions();
    const { putPreviewTheme } = useThemeActions();
    const actions = useThemeEditorActions();
    const { getThemeById } = actions;
    const { theme } = useThemeEditorState();

    const [themeName, setThemeName] = useState("");
    const [revisionID, setRevisionID] = useState();
    const classes = themeEditorClasses();
    const { setIFrameRef } = useIFrameCommunication();
    const { pushSmartLocation } = useLinkContext();

    bodyCSS();

    let themeID = props.match.params.id;

    useFallbackBackUrl("/theme/theme-settings");

    const themeStatus = theme.status;
    const history = useHistory();

    useEffect(() => {
        if (themeStatus === LoadStatus.PENDING && themeID !== undefined) {
            getThemeById(themeID, history);
        }
    }, [themeStatus, themeID, getThemeById, history]);

    const lastStatus = useLastValue(theme.status);
    useEffect(() => {
        if (theme.status === LoadStatus.SUCCESS && lastStatus !== LoadStatus.SUCCESS && theme.data) {
            setThemeName(theme.data.name);
            setRevisionID(theme.data.revisionID);
        }
    }, [theme.status, theme.data, lastStatus]);

    const submitHandler = async event => {
        event.preventDefault();

        if (revisionID !== null && themeID) {
            const theme = await patchThemeWithRevisionID({ themeID: themeID, revisionID: revisionID });
            if (theme) {
                pushSmartLocation(`/theme/theme-settings/${themeID}/revisions`);
            }
        }
    };

    const handleChange = id => {
        setRevisionID(id);
    };

    const handlePreview = async () => {
        putPreviewTheme({ themeID: themeID, type: PreviewStatusType.PREVIEW });
    };

    let content: React.ReactNode;
    if (theme.status === LoadStatus.LOADING || theme.status === LoadStatus.PENDING) {
        content = <Loader />;
    } else if (theme.status === LoadStatus.ERROR || !theme.data) {
        content = <ErrorPage error={theme.error} />;
    } else {
        const tabData = [
            {
                label: t("Styles"),
                panelData: "style",

                contents: (
                    <>
                        <div className={classes.wrapper}>
                            <div className={classes.frame}>
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
                                <ThemeRevisionsPanel themeID={themeID} handleChange={handleChange} />
                            </div>
                        </div>
                    </>
                ),
            },
        ];

        content = (
            <>
                <form onSubmit={submitHandler}>
                    <ActionBar
                        useShadow={false}
                        callToActionTitle={false ? t("Saved") : t("Restore")}
                        anotherCallToActionTitle={"Preview"}
                        title={<ThemeEditorTitle themeName={theme.data?.name} isDisabled={true} />}
                        fullWidth={true}
                        backTitle={t("Back")}
                        isCallToActionLoading={false}
                        isCallToActionDisabled={false}
                        anotherCallToActionLoading={false}
                        anotherCallToActionDisabled={false}
                        handleAnotherSubmit={handlePreview}
                    />
                </form>

                <TextEditorContextProvider>
                    <Tabs data={tabData} />
                </TextEditorContextProvider>
            </>
        );
    }

    return (
        <IframeCommunicationContextProvider>
            <Modal isVisible={true} scrollable={true} titleID={titleID} size={ModalSizes.FULL_SCREEN}>
                {content}
            </Modal>
        </IframeCommunicationContextProvider>
    );
}
