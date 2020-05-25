import React from "react";
import EventParticipantsTabs, { ParticipantData } from "@groups/events/ui/EventParticipantsTabs";

export default {
    component: EventParticipantsTabs,
    title: "Event Participants Tabs",
};

const yes: ParticipantData[] = [
    {
        eventID: 2,
        userID: 1,
        user: {
            userID: 1,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 2,
        user: {
            userID: 2,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 3,
        user: {
            userID: 3,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 4,
        user: {
            userID: 4,
            name: "Mysterious User",
            photoUrl: null,
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 5,
        user: {
            userID: 5,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 6,
        user: {
            userID: 6,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 7,
        user: {
            userID: 7,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 8,
        user: {
            userID: 8,
            name: "Alex",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/914/nFDVYLAK3OF99.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 9,
        user: {
            userID: 9,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "yes",
        },
    },
    {
        eventID: 2,
        userID: 10,
        user: {
            userID: 10,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            attending: "yes",
        },
    },
];

const maybe: ParticipantData[] = [
    {
        eventID: 2,
        userID: 30,
        user: {
            userID: 30,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            attending: "maybe",
        },
    },
    {
        eventID: 2,
        userID: 40,
        user: {
            userID: 40,
            name: "Mysterious User",
            photoUrl: null,
            attending: "maybe",
        },
    },
    {
        eventID: 2,
        userID: 50,
        user: {
            userID: 50,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "maybe",
        },
    },

    {
        eventID: 2,
        userID: 70,
        user: {
            userID: 70,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            attending: "maybe",
        },
    },

    {
        eventID: 2,
        userID: 90,
        user: {
            userID: 90,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            attending: "maybe",
        },
    },
];

const no: ParticipantData = [];

export const ParticipantsTabs = () => {
    return (
        <EventParticipantsTabs
            yesParticipants={yes}
            maybeParticipants={maybe}
            noParticipants={no}
            closeClick={() => alert("close")}
        />
    );
};
