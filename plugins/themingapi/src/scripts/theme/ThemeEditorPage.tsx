/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useEffect, Dispatch, useRef, useCallback, useLayoutEffect } from "react";
import { BrowserRouter } from "react-router-dom";
import { themeEitorClasses } from "./themeEditorStyles";
import { ActionBar } from "@library/headers/ActionBar";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDown, { FlyoutType, DropDownOpenDirection } from "@library/flyouts/DropDown";
import { Tabs } from "@library/sectioning/Tabs";
import TextEditor from "@library/textEditor/TextEditor";
import { useThemeActions } from "./ThemeEditorActions";
import { useThemeEditorState, IThemeAssets } from "./themeEditorReducer";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import { t } from "@vanilla/i18n";
import DropDownItemSeparator from "@vanilla/library/src/scripts/flyouts/items/DropDownItemSeparator";
import { EditIcon } from "@vanilla/library/src/scripts/icons/common";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import Button from "@vanilla/library/src/scripts/forms/Button";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import classNames from "classnames";

interface IProps {
    themeID: string | number;
    type: string;
    name: string;
    assets: IThemeAssets;
}
export default function ThemeEditorPage(props: IProps) {
    const titleID = useUniqueID("themeEditor");
    const classes = themeEitorClasses();
    const { updateAssets, saveTheme } = useThemeActions();
    const actions = useThemeActions();
    const { theme, form } = useThemeEditorState();
    const [header, setHeader] = useState("");
    const [footer, setFooter] = useState("");
    const [js, setJS] = useState("");
    const [css, setCss] = useState("");
    const [isDisabled, setDisabled] = useState(true);
    const [themeName, setThemeName] = useState("");
    const search = window.location.search;
    const params = new URLSearchParams(search);
    const themeId = params.get("themeName");
    const inputRef = useRef<HTMLInputElement>(null); //React.createRef<InputTextBlock>();
    useEffect(() => {
        if (theme.status === LoadStatus.PENDING && themeId !== null) {
            actions.getThemeById(themeId);
            //actions.initAssets({themeID});
        }
    }, [theme]);
    if (theme.status === LoadStatus.LOADING || theme.status === LoadStatus.PENDING || !theme.data) {
        return <Loader />;
    }

    const { name, type, themeID, assets } = theme.data;

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
                        setHeader(newValue ? newValue : "");
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
                        setFooter(newValue ? newValue : "");
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
                        setCss(newValue ? newValue : "");
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
                        setJS(newValue ? newValue : "");
                    }}
                />
            ),
        },
    ];
    const newAssets = {
        header: header,
        footer: footer,
    };
    const handleNameChange = event => {
        event.stopPropagation();
        event.preventDefault();
        setThemeName(event.target.value || "");
    };

    const editThemeName = (ref: React.RefObject<HTMLInputElement>) => {
        setDisabled(false);
        ref.current?.focus();
    };

    const Title = () => {
        //setThemeName(name ? name : " ");
        return (
            <div className={classes.themeName}>
                <InputTextBlock
                    type="text"
                    wrapClassName={classNames(classes.inputWrapper)}
                    disabled={isDisabled}
                    inputProps={{
                        onChange: event => {
                            //event.preventDefault();
                            //handleNameChange;
                            console.log(event.target.value);
                            updateAssets({ name: event.target.value });
                            setThemeName(event.target.value);
                        },
                        disabled: isDisabled,
                        inputRef,
                        value: themeName,
                        inputClassNames: classNames(classes.themeInput),
                    }}
                />

                <Button
                    baseClass={ButtonTypes.ICON_COMPACT}
                    onClick={() => {
                        editThemeName(inputRef);
                    }}
                >
                    <EditIcon className={classes.editIcon} small={true} />
                </Button>
            </div>
        );
    };

    return (
        <BrowserRouter>
            <React.Fragment>
                <Modal scrollable={true} titleID={titleID} size={ModalSizes.FULL_SCREEN}>
                    <form
                        onSubmit={async event => {
                            event.preventDefault();
                            if (themeId !== null) {
                                void saveTheme(newAssets, type, themeName, parseInt(themeId, 10));
                            }
                        }}
                    >
                        <ActionBar
                            callToActionTitle={"Save"}
                            title={<Title />}
                            fullWidth={true}
                            optionsMenu={
                                <DropDown flyoutType={FlyoutType.LIST} openDirection={DropDownOpenDirection.BELOW_LEFT}>
                                    <DropDownItemButton name={t("Copy")} onClick={() => {}} />
                                    <DropDownItemButton name={t("Exit")} onClick={() => {}} />
                                    <DropDownItemSeparator />
                                    <DropDownItemButton name={t("Delete")} onClick={() => {}} />
                                </DropDown>
                            }
                        />

                        <Tabs data={tabData} />
                    </form>
                </Modal>
            </React.Fragment>
        </BrowserRouter>
    );
}
