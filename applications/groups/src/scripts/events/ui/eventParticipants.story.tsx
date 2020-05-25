import React from "react";
import EventParticipants from "@groups/events/ui/EventParticipants";

export default {
    component: EventParticipants,
    title: "Event Participants",
};

const going = [
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Mysterious User",
            photoUrl: null,
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Alex",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/914/nFDVYLAK3OF99.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 100,
        user: {
            userID: 100,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            attending: "yes",
        },
    },
];

export const FullParticipantList = () => {
    return (
        <>
            <EventParticipants participants={going} />
        </>
    );
};
