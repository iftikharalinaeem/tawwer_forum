import React from "react";

export function EventParticipants({ participants }) {
    // console.log(participants);
    return (
        <ul>
            {participants && participants.map(participant => <li key={participant.userID}> {participant.userID} </li>)}
        </ul>
    );
}
