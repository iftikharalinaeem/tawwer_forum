/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { IUser } from "@vanilla/library/src/scripts/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import LinkAsButton from "@library/routing/LinkAsButton";
import { CloseCompactIcon } from "@library/icons/common";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import NumberFormatted from "@library/content/NumberFormatted";
import { t } from "@vanilla/i18n";
import { makeProfileUrl } from "@library/utility/appUtils";

interface IProps {
    user: IUser;
    visible?: boolean;
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
}

interface IVerticalLineProps {
    width: number;
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
    return <div> {label && <div className={classes.label}>{label}</div>} </div>;
}

function Container(props) {
    const classes = userCardClasses();
    return <div className={classes.container}>{props.children}</div>;
}

function ButtonContainer(props) {
    const classes = userCardClasses();

    return <div className={classes.buttonContainer}>{props.children}</div>;
}

function Stat(props: IStatProps) {
    const classes = userCardClasses();

    const { count, text } = props;
    return (
        <div className={classes.stat}>
            <div className={classes.count}>
                <NumberFormatted value={count || 0} />
            </div>
            <div> {text} </div>
        </div>
    );
}

function VerticalLine(props: IVerticalLineProps) {
    const classes = userCardClasses();
    const { width } = props;
    return (
        <div>
            <hr className={classes.vertical} style={{ width: `${width}px`, height: "100%" }} />{" "}
        </div>
    );
}

function Date(props: IDateProps) {
    const classes = userCardClasses();
    const { text, date } = props;
    return <div className={classes.date}>{`${text}: ${date}`} </div>;
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
    const { user, visible } = props;
    const [open, toggleOpen] = useState(visible || false);

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
                <UserPhoto userInfo={user} size={UserPhotoSize.LARGE} />
            </Container>

            <Container>
                <Name name={user.name} />
            </Container>

            <Container>
                <Label label={user.label} />
            </Container>

            <Permission permission={"email.view"} mode={PermissionMode.GLOBAL}>
                <Container>
                    <a className={classes.email} href={`mailto:${user.email}`}>
                        {user.email}
                    </a>
                </Container>
            </Permission>

            <Container>
                <ButtonContainer>
                    <LinkAsButton
                        to={makeProfileUrl(user.name)}
                        baseClass={ButtonTypes.STANDARD}
                        className={classes.button}
                    >
                        {t("View Profile")}
                    </LinkAsButton>
                </ButtonContainer>

                <ButtonContainer>
                    <LinkAsButton
                        to={`/messages/add/${user.name}`}
                        baseClass={ButtonTypes.STANDARD}
                        className={classes.button}
                    >
                        {t("Message")}
                    </LinkAsButton>
                </ButtonContainer>
            </Container>

            <DropDownSection className={classes.section} noSeparator={false} title={""}>
                <Container>
                    <Stat count={user.countDiscussions} text={t("Discussions")} />
                    <VerticalLine width={1} />
                    <Stat count={user.countComments} text={t("Comments")} />
                </Container>
            </DropDownSection>

            <DropDownSection className={classes.section} noSeparator={false} title={""}>
                <Container>
                    <Date text={t("Joined")} date={user.dateJoined} />
                    <Date text={t("Last Active")} date={user.dateLastActive} />
                </Container>
            </DropDownSection>
        </DropDown>
    );
}
