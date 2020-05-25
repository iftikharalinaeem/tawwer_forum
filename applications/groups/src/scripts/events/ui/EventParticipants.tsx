import React from "react";

export default function EventParticipants({ participants }) {
    console.log(participants);
    return (
        <ul>
            {participants &&
                participants.map(participant => (
                    <li key={participant.userID}>
                        {participant.user.name} - {participant.user.userID}
                    </li>
                ))}
        </ul>
    );
}
