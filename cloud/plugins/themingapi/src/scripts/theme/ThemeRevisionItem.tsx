import React from "react";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { IUserFragment } from "@library/@types/api/users";
import { visibility } from "@library/styles/styleHelpersVisibility";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { dropdownSwitchButtonClasses } from "@library/flyouts/dropDownSwitchButtonStyles";
import { CheckCompactIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { ITheme } from "@library/theming/themeReducer";
import { themeRevisionPageClasses } from "@themingapi/theme/themeRevisionsPageStyles";

interface IProps {
    revision: ITheme;
    isSelected?: boolean;
    userInfo: IUserFragment;
    onClick?: (event: any) => void;
    isActive?: boolean;
}

export function ThemeRevisionItem(props: IProps) {
    const { revision, isSelected, userInfo, isActive } = props;
    const visibilityClasses = visibility();
    const classes = dropdownSwitchButtonClasses();
    const revisionPageClasses = themeRevisionPageClasses();

    const checkStatus = isSelected ? (
        <>
            <CheckCompactIcon aria-hidden={true} />
            <span className={visibilityClasses.visuallyHidden}>{t("on")}</span>
        </>
    ) : (
        <>
            <span className={visibilityClasses.visuallyHidden}>{t("off")}</span>
        </>
    );

    const content = (
        <>
            <div className={revisionPageClasses.revisionItem}>
                <UserPhoto userInfo={userInfo} size={UserPhotoSize.MEDIUM} />
                <div className={revisionPageClasses.itemLabelContainer}>
                    <span className={classNames(revisionPageClasses.itemLabel, revisionPageClasses.userNameFont)}>
                        {userInfo.name}
                    </span>
                    <span className={classNames(revisionPageClasses.labelMetas, revisionPageClasses.itemLabel)}>
                        <DateTime timestamp={revision.dateInserted} type={DateFormats.EXTENDED} />
                    </span>
                    {isActive && (
                        <span className={classNames(revisionPageClasses.labelMetas, revisionPageClasses.activeItem)}>
                            Current
                        </span>
                    )}
                </div>
                <span className={classNames(classes.checkContainer, "sc-only")}>{checkStatus}</span>
            </div>
        </>
    );

    return (
        <DropDownItemButton onClick={props.onClick} role={"switch"} aria-checked={isSelected}>
            {content}
        </DropDownItemButton>
    );
}