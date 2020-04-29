/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { ActionBar } from "@library/headers/ActionBar";
import { Tabs } from "@library/sectioning/Tabs";
import TextEditor, { TextEditorContextProvider } from "@library/textEditor/TextEditor";
import { IframeCommunicationContextProvider } from "@themingapi/theme/IframeCommunicationContext";
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
import { useHistory, useParams } from "react-router-dom";
import ThemeEditor from "./ThemeEditor";
import { useThemeEditorActions } from "./ThemeEditorActions";
import { useThemeEditorState } from "./themeEditorReducer";
import { bodyCSS } from "@vanilla/library/src/scripts/layout/bodyStyles";
import ModalConfirm from "@library/modal/ModalConfirm";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import { makeThemeEditorUrl } from "@themingapi/routes/makeThemeEditorUrl";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";

interface IProps {}

export default function ThemeEditorPage(props: IProps) {
    const titleID = useUniqueID("themeEditor");
    const { updateAssets, saveTheme, getThemeById } = useThemeEditorActions();
    const { theme, form, formSubmit } = useThemeEditorState();
    const { assets } = form;
    const [themeName, setThemeName] = useState("");
    const [showUserNotificationModal, setShowUserNotificationModal] = useState(false);
    const [disabledRouteChangePrompt, setDisableRouteChangePrompt] = useState(true);
    const { pushSmartLocation } = useLinkContext();
    bodyCSS();

    const history = useHistory();
    let themeID = useParams<{
        id: string;
    }>().id;

    const DEFAULT_THEME = "theme-foundation";

    const getTemplateName = () => {
        const query = qs.parse(history.location.search.replace(/^\?/, ""));
        return history.location.pathname === "/theme/theme-settings/add" && !query.templateName
            ? DEFAULT_THEME
            : query.templateName;
    };

    if (themeID === undefined) {
        themeID = getTemplateName();
    }

    useFallbackBackUrl("/theme/theme-settings");

    const themeStatus = theme.status;
    useEffect(() => {
        if (themeStatus === LoadStatus.PENDING && themeID !== undefined) {
            getThemeById(themeID, history);
        }
    }, [themeStatus, themeID, getThemeById, history]);

    const lastStatus = useLastValue(theme.status);
    useEffect(() => {
        if (theme.status === LoadStatus.SUCCESS && lastStatus !== LoadStatus.SUCCESS && theme.data) {
            setThemeName(theme.data.name);
        }
    }, [theme.status, theme.data, lastStatus]);

    const submitHandler = async event => {
        event.preventDefault();

        if (themeID !== null) {
            if (form.errors) {
                return false;
            } else {
                setDisableRouteChangePrompt(true);
                const theme = await saveTheme();
                if (theme) {
                    history.replace(makeThemeEditorUrl({ themeID: theme.themeID }));
                }
            }
        }
    };

    let isFormEdited = useThemeEditorState()?.form.edited;

    let content: React.ReactNode;

    let sendMessage;
    const getSendMessage = (sendMessageFunction: (message: {}) => void) => {
        sendMessage = sendMessageFunction;
        window.sendMessage = sendMessage;
    };

    const routeChangePrompt = useRouteChangePrompt(
        t(
            "You are leaving the theme editor without saving your changes. Make sure your updates are saved before exiting.",
        ),
        disabledRouteChangePrompt,
    );

    const handleCancelEditingTheme = () => {
        if (isFormEdited) {
            setShowUserNotificationModal(true);
            setDisableRouteChangePrompt(true);
        } else {
            setDisableRouteChangePrompt(true);
            pushSmartLocation("/theme/theme-settings");
        }
    };

    const navigateToThemePage = () => {
        pushSmartLocation("/theme/theme-settings");
    };

    const closeModel = () => {
        setShowUserNotificationModal(false);
        setDisableRouteChangePrompt(false);
    };

    useEffect(() => {
        if (isFormEdited) {
            setDisableRouteChangePrompt(false);
        }
    }, [isFormEdited]);

    if (theme.status === LoadStatus.LOADING || theme.status === LoadStatus.PENDING) {
        content = <Loader />;
    } else if (theme.status === LoadStatus.ERROR || !theme.data) {
        content = <ErrorPage error={theme.error} />;
    } else if (formSubmit.status === LoadStatus.ERROR) {
        content = <ErrorPage apiError={formSubmit.error} />;
    } else {
        const tabData = [
            {
                label: t("Styles"),
                panelData: "style",

                contents: (
                    <ThemeEditor
                        themeID={themeID ?? getTemplateName()}
                        variables={theme.data.assets.variables}
                        getSendMessage={getSendMessage}
                    />
                ),
            },
            {
                label: t("Header"),
                panelData: "header",
                contents: (
                    <TextEditor
                        language={"html"}
                        value={assets.header?.data}
                        onChange={(event, newValue) => {
                            updateAssets({
                                assets: {
                                    header: {
                                        data: newValue,
                                        type: "html",
                                    },
                                },
                            });
                        }}
                    />
                ),
            },

            {
                label: t("Footer"),
                panelData: "footer",
                contents: (
                    <TextEditor
                        language={"html"}
                        value={assets.footer?.data}
                        onChange={(event, newValue) => {
                            updateAssets({
                                assets: {
                                    footer: {
                                        data: newValue,
                                        type: "html",
                                    },
                                },
                            });
                        }}
                    />
                ),
            },
            {
                label: t("CSS"),
                panelData: "css",
                contents: (
                    <TextEditor
                        language={"css"}
                        value={assets.styles}
                        onChange={(event, newValue) => {
                            updateAssets({
                                assets: { styles: newValue },
                            });
                        }}
                    />
                ),
            },
            {
                label: t("JavaScript"),
                panelData: "js",
                contents: (
                    <TextEditor
                        language={"javascript"}
                        value={assets.javascript}
                        onChange={(event, newValue) => {
                            updateAssets({
                                assets: { javascript: newValue },
                            });
                        }}
                    />
                ),
            },
        ];

        content = (
            <>
                <ModalConfirm
                    title={t("Unsaved Changes")}
                    onConfirm={navigateToThemePage}
                    isVisible={showUserNotificationModal}
                    onCancel={closeModel}
                    confirmTitle={t("Exit")}
                >
                    {t(
                        "You are leaving the theme editor without saving your changes. Make sure your updates are saved before exiting.",
                    )}
                </ModalConfirm>
                <form onSubmit={submitHandler}>
                    <ActionBar
                        useShadow={false}
                        callToActionTitle={formSubmit.status === LoadStatus.SUCCESS ? t("Saved") : t("Save")}
                        title={<ThemeEditorTitle themeName={theme.data.name} pageType={form.pageType} />}
                        fullWidth={true}
                        backTitle={t("Back")}
                        isCallToActionLoading={formSubmit.status === LoadStatus.LOADING}
                        isCallToActionDisabled={!!form.errors}
                        handleCancel={handleCancelEditingTheme}
                        optionsMenu={
                            <>
                                {/* WIP not wired up. */}
                                {/* <DropDown
                                        flyoutType={FlyoutType.LIST}
                                        openDirection={DropDownOpenDirection.BELOW_LEFT}
                                    >
                                        <DropDownItemButton name={t("Copy")} onClick={() => {}} />
                                        <DropDownItemButton name={t("Exit")} onClick={() => {}} />
                                        <DropDownItemSeparator />
                                        <DropDownItemButton name={t("Delete")} onClick={() => {}} />
                                    </DropDown> */}
                            </>
                        }
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
