/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { ActionBar } from "@library/headers/ActionBar";
import { Tabs } from "@library/sectioning/Tabs";
import TextEditor, { TextEditorContextProvider } from "@library/textEditor/TextEditor";
import { t } from "@vanilla/i18n";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import DropDownItemSeparator from "@vanilla/library/src/scripts/flyouts/items/DropDownItemSeparator";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { EditIcon } from "@vanilla/library/src/scripts/icons/common";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import { useFallbackBackUrl } from "@vanilla/library/src/scripts/routing/links/BackRoutingProvider";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";
import { useLastValue } from "@vanilla/react-utils";
import classNames from "classnames";
import qs from "qs";
import React, { useEffect, useRef, useState } from "react";
import { BrowserRouter, RouteComponentProps, useHistory } from "react-router-dom";
import { useThemeActions } from "./ThemeEditorActions";
import { IThemeAssets, useThemeEditorState } from "./themeEditorReducer";
import { themeEitorClasses } from "./themeEditorStyles";
import { formatUrl } from "@library/utility/appUtils";

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

export default function ThemeEditorPage(props: IProps, ownProps: IOwnProps) {
    const titleID = useUniqueID("themeEditor");
    const { updateAssets, saveTheme } = useThemeActions();
    const actions = useThemeActions();
    const { getThemeById } = actions;
    const { theme, form, formSubmit } = useThemeEditorState();
    const [themeName, setThemeName] = useState("");
    let themeID = props.match.params.id;
    const getTemplateName = () => {
        const query = qs.parse(props.history.location.search.replace(/^\?/, ""));
        return query.templateName;
    };
    if (themeID === undefined) {
        themeID = getTemplateName();
    }

    useFallbackBackUrl("/theme/theme-settings");

    const themeStatus = theme.status;
    useEffect(() => {
        if (themeStatus === LoadStatus.PENDING && themeID !== undefined) {
            getThemeById(themeID);
        }
    }, [themeStatus, themeID, getThemeById]);

    const lastStatus = useLastValue(theme.status);
    useEffect(() => {
        if (theme.status === LoadStatus.SUCCESS && lastStatus !== LoadStatus.SUCCESS && theme.data) {
            setThemeName(theme.data.name);
        }
    }, [theme.status, theme.data, lastStatus]);

    const history = useHistory();
    const submitHandler = async event => {
        event.preventDefault();
        if (themeID !== null) {
            await saveTheme(history);
            window.location.href = formatUrl("/theme/theme-settings", true);
        }
    };

    if (theme.status === LoadStatus.LOADING || theme.status === LoadStatus.PENDING || !theme.data) {
        return <Loader />;
    }
    const { assets } = form;
    const tabData = [
        {
            label: "Header",
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
            label: "Footer",
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
            label: "CSS",
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
            label: "JS",
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

    return (
        <BrowserRouter>
            <React.Fragment>
                <Modal isVisible={true} scrollable={true} titleID={titleID} size={ModalSizes.FULL_SCREEN}>
                    <form onSubmit={submitHandler}>
                        <ActionBar
                            useShadow={false}
                            callToActionTitle={t("Save")}
                            title={<Title themeName={theme.data.name} />}
                            fullWidth={true}
                            isCallToActionLoading={formSubmit.status === LoadStatus.LOADING}
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

                        <TextEditorContextProvider>
                            <Tabs data={tabData} />
                        </TextEditorContextProvider>
                    </form>
                </Modal>
            </React.Fragment>
        </BrowserRouter>
    );
}
interface IThemeTitleProps {
    isDisabled?: boolean;
    updateAssets?: void;
    setThemeName?: string;
    themeName?: string;
    editThemeName?: void;
}
export const Title = (props: IThemeTitleProps) => {
    const { updateAssets } = useThemeActions();
    const inputRef = useRef<HTMLInputElement | null>(null);
    const [isDisabled, setDisabled] = useState(true);
    const [name, setName] = useState(props.themeName);
    const classes = themeEitorClasses();

    const editThemeName = () => {
        setDisabled(false);
        setImmediate(() => {
            inputRef.current?.focus();
        });
    };

    return (
        <li className={classes.themeName}>
            <InputTextBlock
                wrapClassName={classNames(classes.inputWrapper)}
                disabled={isDisabled}
                inputProps={{
                    required: true,
                    inputClassNames: classNames(classes.themeInput),
                    onChange: event => {
                        updateAssets({ name: event.target.value });
                        setName(event.target.value);
                    },
                    disabled: isDisabled,
                    inputRef,
                    value: name,
                }}
            />

            <Button
                baseClass={ButtonTypes.ICON_COMPACT}
                onClick={() => {
                    editThemeName();
                }}
            >
                <EditIcon className={classes.editIcon} small={true} />
            </Button>
        </li>
    );
};
