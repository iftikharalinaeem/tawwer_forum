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
import React from "react";

interface IProps {
    data: IUserFragment[];
    title: string;
    extra: number;
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
    const { data, maxCount = 10, extra, separator = false, depth = 2, title, emptyMessage } = props;
    const empty = data.length === 0;
    const classes = eventsClasses();
    const HeadingTag = `h${depth}` as "h1";

    const extraCount = extra - maxCount;

    return (
        <section className={classNames(classes.section, props.className)}>
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
                            +<NumberFormatted value={extraCount} />
                        </li>
                    )}
                </ul>
            )}
        </section>
    );
}
