/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode, useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import LinkAsButton from "@library/routing/LinkAsButton";
import { CloseCompactIcon } from "@library/icons/common";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import NumberFormatted from "@library/content/NumberFormatted";
import { t } from "@vanilla/i18n";
import { makeProfileUrl } from "@library/utility/appUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import DateTime from "@library/content/DateTime";
import classNames from "classnames";

export interface IUserCardInfo {
    email: string;
    userID: number;
    name: string;
    photoUrl: string;
    dateLastActive?: string;
    dateJoined?: string;
    label?: string | null;
    countDiscussions: number;
    countComments: number;
}

interface IProps {
    user: IUserCardInfo;
    visible?: boolean;
}

interface IContainerProps {
    children: ReactNode;
    borderTop?: boolean;
}

interface INameProps {
    name: string;
}

interface ILabelProps {
    label?: string | null;
}

interface IStatProps {
    count?: number;
    text: string;
    position: "left" | "right";
}

interface IDateProps {
    text: string;
    date: string | undefined;
}

interface IHeaderProps {
    onClick: () => void;
}

function Name(props: INameProps) {
    const classes = userCardClasses();
    const { name } = props;
    return <div className={classes.name}> {name} </div>;
}

function Label(props: ILabelProps) {
    const classes = userCardClasses();
    const { label } = props;
    return label ? <div className={classes.label}>{label}</div> : null;
}

function Container(props: IContainerProps) {
    const { borderTop } = props;
    const classes = userCardClasses();
    return (
        <div className={classNames(classes.container, { [classes.containerWithBorder]: borderTop })}>
            {props.children}
        </div>
    );
}

function ButtonContainer(props) {
    const classes = userCardClasses();

    return <div className={classes.buttonContainer}>{props.children}</div>;
}

function Stat(props: IStatProps) {
    const classes = userCardClasses();

    const { count, text, position } = props;
    return (
        <div
            className={classNames(classes.stat, {
                [classes.statLeft]: position === "left",
                [classes.statRight]: position === "right",
            })}
        >
            <div className={classes.count}>
                <NumberFormatted value={count || 0} />
            </div>
            <div className={classes.statLabel}>{text}</div>
        </div>
    );
}

function Date(props: IDateProps) {
    const classes = userCardClasses();
    const { text, date } = props;
    return (
        <div className={classes.date}>
            {`${text}: `} <DateTime timestamp={date} />
        </div>
    );
}

function Header(props: IHeaderProps) {
    const classes = userCardClasses();
    const { onClick } = props;
    const device = useDevice();
    const isCompact = device === Devices.MOBILE || device === Devices.XS;

    return (
        <div className={classes.header}>
            {isCompact && (
                <Button className={classes.close} onClick={onClick} baseClass={ButtonTypes.ICON}>
                    <>
                        <CloseCompactIcon />
                        <ScreenReaderContent>{t("Close")}</ScreenReaderContent>
                    </>
                </Button>
            )}
        </div>
    );
}

export default function PopupUserCard(props: IProps) {
    const classes = userCardClasses();
    const { user, visible } = props;
    const [open, toggleOpen] = useState(visible || false);
    const device = useDevice();

    const isCompact = device === Devices.MOBILE || device === Devices.XS;
    const photoSize: UserPhotoSize = isCompact ? UserPhotoSize.XLARGE : UserPhotoSize.LARGE;

    const userInfo: IUserFragment = {
        userID: user.userID,
        name: user.name,
        photoUrl: user.photoUrl,
        dateLastActive: user.dateLastActive || null,
        label: user.label || null,
    };

    return (
        <DropDown
            buttonBaseClass={ButtonTypes.TEXT}
            buttonContents={user.name}
            buttonClassName={classes.link}
            selfPadded={true}
            flyoutType={FlyoutType.FRAME}
            isVisible={open}
            onVisibilityChange={isVisible => toggleOpen(isVisible)}
        >
            <Header onClick={() => toggleOpen(!open)} />

            <UserPhoto userInfo={userInfo} size={photoSize} className={classes.userPhoto} />

            <Container>
                <Name name={user.name} />
                {/* We don't  want this section to show at all if there's no label */}
                {user.label && <Label label={user.label} />}

                <Permission permission={"email.view"} mode={PermissionMode.GLOBAL}>
                    <a className={classes.email} href={`mailto:${user.email}`}>
                        {user.email}
                    </a>
                </Permission>
            </Container>

            <div className={classNames(classes.container, classes.actionContainer)}>
                <ButtonContainer>
                    <LinkAsButton
                        to={makeProfileUrl(user.name)}
                        baseClass={ButtonTypes.STANDARD_ROUND}
                        className={classes.button}
                    >
                        {t("View Profile")}
                    </LinkAsButton>
                </ButtonContainer>

                <ButtonContainer>
                    <LinkAsButton
                        to={`/messages/add/${user.name}`}
                        baseClass={ButtonTypes.STANDARD_ROUND}
                        className={classes.button}
                    >
                        {t("Message")}
                    </LinkAsButton>
                </ButtonContainer>
            </div>

            <Container borderTop={true}>
                <Stat count={user.countDiscussions} text={t("Discussions")} position={"left"} />
                <Stat count={user.countComments} text={t("Comments")} position={"right"} />
            </Container>

            <Container borderTop={true}>
                <Date text={t("Joined")} date={user.dateJoined} />
                <Date text={t("Last Active")} date={user.dateLastActive} />
            </Container>
        </DropDown>
    );
}