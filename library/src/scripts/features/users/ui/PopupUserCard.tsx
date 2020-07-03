/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { IUserFragment, IUser } from "@vanilla/library/src/scripts/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import LinkAsButton from "@library/routing/LinkAsButton";
import { CloseCompactIcon } from "@library/icons/common";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import NumberFormatted from "@library/content/NumberFormatted";
import { t } from "@vanilla/i18n";

interface IProps {
    userInfo: IUser;
    links: {
        profileLink: string;
        messageLink: string;
    };
    stats: {
        discussions: number;
        comments: number;
    };
}

interface INameProps {
    name: string;
}

interface ILabelProps {
    label?: string | null;
}

interface ISeparatorProps {
    width: number;
}

interface IStatProps {
    count: number;
    text: string;
}

interface IVerticalLineProps {
    width: number;
    height: number;
}

interface IDateProps {
    text: string;
    date?: string | null;
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
    return <div className={classes.label}>{label}</div>;
}

function Container(props) {
    const classes = userCardClasses();
    return <div className={classes.container}>{props.children}</div>;
}

function Separator(props: ISeparatorProps) {
    const { width } = props;
    return <div style={{ width: `${width}px` }}> </div>;
}

function Stat(props: IStatProps) {
    const classes = userCardClasses();

    const { count, text } = props;
    return (
        <div className={classes.stat}>
            <div className={classes.count}>
                <NumberFormatted value={count} />
            </div>
            <div> {text} </div>
        </div>
    );
}

function VerticalLine(props: IVerticalLineProps) {
    const classes = userCardClasses();
    const { width, height } = props;
    return <hr className={classes.vertical} style={{ width: `${width}px`, height: `${height}px` }} />;
}

function Date(props: IDateProps) {
    const { text, date } = props;
    return <span>{`${text}: ${date}`}</span>;
}

function Header(props: IHeaderProps) {
    const classes = userCardClasses();
    const { onClick } = props;
    return (
        <div className={classes.header}>
            <Button onClick={onClick} baseClass={ButtonTypes.ICON}>
                <CloseCompactIcon />
            </Button>
        </div>
    );
}

export default function PopupUserCard(props: IProps) {
    const classes = userCardClasses();
    const { userInfo, stats, links } = props;
    const [open, toggleOpen] = useState(false);

    return (
        <DropDown
            buttonBaseClass={ButtonTypes.TEXT_PRIMARY}
            buttonContents={"Val"}
            selfPadded={true}
            flyoutType={FlyoutType.FRAME}
            isVisible={open}
            onVisibilityChange={isVisibble => toggleOpen(isVisibble)}
        >
            <Header onClick={() => toggleOpen(!open)} />

            <Container>
                <UserPhoto userInfo={userInfo} size={UserPhotoSize.LARGE} />
            </Container>

            <Container>
                <Name name={userInfo.name} />
            </Container>

            <Container>
                <Label label={userInfo.label} />
            </Container>

            <Permission permission={"email.view"} mode={PermissionMode.GLOBAL}>
                <Container>
                    <a className={classes.email} href={`mailto:${userInfo.email}`}>
                        {userInfo.email}
                    </a>
                </Container>
            </Permission>

            <Container>
                <LinkAsButton to={links.profileLink} baseClass={ButtonTypes.STANDARD} className={classes.button}>
                    {t("View Profile")}
                </LinkAsButton>

                <Separator width={16} />

                <LinkAsButton
                    to={`/messages/add/${userInfo.name}`}
                    baseClass={ButtonTypes.STANDARD}
                    className={classes.button}
                >
                    {t("Message")}
                </LinkAsButton>
            </Container>

            <DropDownSection noSeparator={false} title={""}>
                <Container>
                    <Stat count={stats.discussions} text={"Discussions"} />
                    <Separator width={29} />
                    <VerticalLine width={1} height={58} />
                    <Separator width={29} />
                    <Stat count={stats.comments} text={"Comments"} />
                </Container>
            </DropDownSection>

            <DropDownSection className={classes.section} noSeparator={false} title={""}>
                <Container>
                    <Date text={"Joined"} date={userInfo.dateJoined} />
                    <Separator width={40} />
                    <Date text={"Last Active"} date={userInfo.dateLastActive} />
                </Container>
            </DropDownSection>
        </DropDown>
    );
}
