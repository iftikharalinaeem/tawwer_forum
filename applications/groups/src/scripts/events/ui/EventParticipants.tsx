import React from "react";

export default function EventParticipants({ participants }) {
    return (
        <ul>
            {participants && participants.map(participant => <li key={participant.userID}> {participant.userID} </li>)}
        </ul>
    );
}
