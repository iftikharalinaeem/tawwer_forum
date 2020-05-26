/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { IUserFragment } from "@library/@types/api/users";
import NumberFormatted from "@library/content/NumberFormatted";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";
import React, { useState } from "react";
import { EventParticipantsTabModule } from "../modules/EventParticipantsTabModule";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";
import Button from "@vanilla/library/src/scripts/forms/Button";

interface IProps {
    eventID: number;
    data: IUserFragment[];
    title: string;
    extra: number | undefined;
    separator?: boolean;
    depth?: 2 | 3;
    maxCount?: number;
    emptyMessage?: string;
    className?: string;
}

/**
 * Component for displaying an event details
 */
export function EventAttendees(props: IProps) {
    const { eventID, data, maxCount = 10, extra = 0, separator = false, depth = 2, title, emptyMessage } = props;
    const empty = data.length === 0;
    const classes = eventsClasses();
    const HeadingTag = `h${depth}` as "h1";

    const extraCount = extra - maxCount;

    const [visible, setVisible] = useState(false);

    return (
        <section className={classNames(classes.section, props.className)}>
            {visible && <EventParticipantsTabModule eventID={eventID} visible={visible} />}
            {separator && <hr className={classes.separator} />}
            <HeadingTag className={classes.sectionTitle}>{title}</HeadingTag>
            {empty && <Paragraph className={classes.noAttendees}>{emptyMessage}</Paragraph>}
            {!empty && (
                <ul className={classes.attendeeList}>
                    {data.map((user, i) => {
                        if (i >= maxCount) {
                            return null;
                        }
                        return (
                            <li
                                className={classNames(classes.attendee, {
                                    isLast: i === data.length - 1,
                                })}
                                key={i}
                            >
                                <UserPhoto
                                    size={UserPhotoSize.MEDIUM}
                                    className={classes.attendeePhoto}
                                    userInfo={user}
                                />
                            </li>
                        );
                    })}
                    {extraCount > 0 && (
                        <li className={classes.attendeePlus} key={data.length}>
                            <Button
                                className={classes.participantsPopUpButton}
                                onClick={() => setVisible(true)}
                                baseClass={ButtonTypes.ICON_COMPACT}
                            >
                                <span style={{ display: "inline-block" }}>
                                    +<NumberFormatted value={extraCount} />
                                </span>
                            </Button>
                        </li>
                    )}
                </ul>
            )}
        </section>
    );
}
