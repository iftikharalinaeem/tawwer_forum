/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { eventParticipantsClasses } from "@groups/events/ui/eventParticipantsStyles";
import { IUserFragment } from "@library/@types/api/users";
import NumberFormatted from "@library/content/NumberFormatted";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";
import React from "react";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { t } from "@vanilla/i18n";
import { IEventDetailActionType } from "@groups/events/ui/EventDetails";

interface IProps {
    eventID: number;
    data?: IUserFragment[] | undefined;
    title: string;
    extra: number | undefined;
    separator?: boolean;
    depth?: 2 | 3;
    maxCount?: number;
    emptyMessage?: string;
    className?: string;
    dispatchDetail: (value: any) => void;
}

/**
 * Component for displaying an event details
 */
export function EventAttendees(props: IProps) {
    const {
        eventID,
        data = [],
        maxCount = 10,
        extra = 0,
        separator = false,
        depth = 2,
        title,
        emptyMessage,
        dispatchDetail,
    } = props;
    const empty = data?.length === 0;
    const classes = eventsClasses();
    const HeadingTag = `h${depth}` as "h1";

    const extraCount = extra - maxCount;

    const getIndex = (title: string) => {
        switch (title) {
            case "Going":
                return 0;
            case "Maybe":
                return 1;
            case "Not going":
                return 2;
            default:
                return 0;
        }
    };

    const tooltipText = "View all attendees";

    const openModal = () => {
        dispatchDetail({ type: IEventDetailActionType.SET_VISIBLE_MODAL, visible: true });
        dispatchDetail({ type: IEventDetailActionType.SET_DEFAULT_TAB_INDEX, index: getIndex(title) });
    };

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
                                    isLast: i === data.length - 1 || i === maxCount - 1,
                                })}
                                key={i}
                            >
                                <Button baseClass={ButtonTypes.TEXT} onClick={openModal}>
                                    <UserPhoto
                                        size={UserPhotoSize.MEDIUM}
                                        className={classes.attendeePhoto}
                                        userInfo={user}
                                    />
                                </Button>
                            </li>
                        );
                    })}
                    {extraCount > 0 && (
                        <li className={classes.attendeePlus} key={data.length}>
                            <Button onClick={openModal} baseClass={ButtonTypes.TEXT}>
                                <span style={{ display: "inline-block" }}>
                                    +
                                    <NumberFormatted value={extraCount} title={t(tooltipText)} />
                                </span>
                            </Button>
                        </li>
                    )}
                </ul>
            )}
        </section>
    );
}