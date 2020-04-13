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
import { formatUrl } from "@vanilla/library/src/scripts/utility/appUtils";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";
import { useLastValue } from "@vanilla/react-utils";
import qs from "qs";
import React, { useEffect, useState } from "react";
import { RouteComponentProps, useHistory } from "react-router-dom";
import ThemeEditor from "./ThemeEditor";
import { useThemeActions } from "./ThemeEditorActions";
import { useThemeEditorState } from "./themeEditorReducer";
import { IThemeAssets } from "@vanilla/library/src/scripts/theming/themeReducer";
import { bodyCSS } from "@vanilla/library/src/scripts/layout/bodyStyles";

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

export default function ThemeEditorPage(this: any, props: IProps, ownProps: IOwnProps) {
    const titleID = useUniqueID("themeEditor");
    const { updateAssets, saveTheme } = useThemeActions();
    const actions = useThemeActions();
    const { getThemeById } = actions;
    const { theme, form, formSubmit } = useThemeEditorState();
    const { assets } = form;
    const [themeName, setThemeName] = useState("");
    bodyCSS();

    let themeID = props.match.params.id;

    const DEFAULT_THEME = "theme-foundation";

    const getTemplateName = () => {
        const query = qs.parse(props.history.location.search.replace(/^\?/, ""));
        return props.history.location.pathname === "/theme/theme-settings/add" && !query.templateName
            ? DEFAULT_THEME
            : query.templateName;
    };

    if (themeID === undefined) {
        themeID = getTemplateName();
    }

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
        }
    }, [theme.status, theme.data, lastStatus]);

    const submitHandler = async event => {
        event.preventDefault();

        if (themeID !== null) {
            if (assets.variables) {
                updateAssets({
                    assets: {
                        variables: {
                            data: JSON.stringify(assets.variables.data),
                            type: "json",
                        },
                    },
                });
            }
            if (form.errors) {
                return false;
            } else {
                await saveTheme();
                window.location.href = formatUrl("/theme/theme-settings", true);
            }
        }
    };

    let content: React.ReactNode;

    let sendMessage;
    const getSendMessage = (sendMessageFunction: (message: {}) => void) => {
        sendMessage = sendMessageFunction;
        window.sendMessage = sendMessage;
    };

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
                            updateAssets({ assets: { styles: newValue } });
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
                            updateAssets({ assets: { javascript: newValue } });
                        }}
                    />
                ),
            },
        ];

        content = (
            <>
                <form onSubmit={submitHandler}>
                    <ActionBar
                        useShadow={false}
                        callToActionTitle={t("Save")}
                        title={<ThemeEditorTitle themeName={theme.data.name} pageType={form.pageType} />}
                        fullWidth={true}
                        isCallToActionLoading={formSubmit.status === LoadStatus.LOADING}
                        isCallToActionDisabled={!!form.errors}
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
