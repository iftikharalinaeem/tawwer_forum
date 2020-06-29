/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { userCardClasses } from "@library/features/search/popupUserCardStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import LinkAsButton from "@library/routing/LinkAsButton";

interface IProps {
    userInfo: IUserFragment;
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
    return <div style={{ width: `${width}px` }}></div>;
}

function Stat(props: IStatProps) {
    const classes = userCardClasses();

    const { count, text } = props;
    return (
        <div className={classes.stat}>
            <div className={classes.count}> {count} </div>
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

export default function PopupUserCard(props: IProps) {
    const classes = userCardClasses();
    const { userInfo, stats, links } = props;
    return (
        <DropDown selfPadded={true} flyoutType={FlyoutType.FRAME}>
            <Container>
                <UserPhoto userInfo={userInfo} size={UserPhotoSize.LARGE} />
            </Container>

            <Container>
                <Name name={userInfo.name} />
            </Container>

            <Container>
                <Label label={userInfo.label} />
            </Container>

            <Container>
                <LinkAsButton to={links.profileLink} baseClass={ButtonTypes.STANDARD} className={classes.button}>
                    View Profile
                </LinkAsButton>

                <Separator width={16} />

                <LinkAsButton to={links.messageLink} baseClass={ButtonTypes.STANDARD} className={classes.button}>
                    Message
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

            <DropDownSection noSeparator={false} title={""}>
                <Container>
                    <Date text={"Joined"} date={userInfo.dateJoined} />
                    <Separator width={40} />
                    <Date text={"Last Active"} date={userInfo.dateLastActive} />
                </Container>
            </DropDownSection>
        </DropDown>
    );
}
