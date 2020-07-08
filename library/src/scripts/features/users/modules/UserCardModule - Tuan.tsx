// /**
//  * @copyright 2009-2020 Vanilla Forums Inc.
//  * @license GPL-2.0-only
//  */
//
// import React, { ReactNode, useState } from "react";
// import { useUser } from "@library/features/users/userHooks";
// import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
// import Loader from "@library/loaders/Loader";
// import ErrorMessages from "@library/forms/ErrorMessages";
// import { notEmpty } from "@vanilla/utils";
// import PopupUserCard, { IUserCardInfo } from "@library/features/users/ui/PopupUserCard";
//
// interface IProps {
//     userID: number;
//     buttonContent?: ReactNode | string;
//     openAsModal?: boolean;
// }
//
// interface IUserCardProps {
//     userID: number;
//     buttonContent?: ReactNode | string;
//     openAsModal?: boolean;
//     visible?: boolean;
// }
//
// function UserCard(props: IUserCardProps) {
//     const { userID, buttonContent, openAsModal, visible } = props;
//     const user = useUser({ userID });
//
//     if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(user.status) && !user.data) {
//         return <Loader />;
//     }
//
//     if (!user.data || user.error) {
//         return <ErrorMessages errors={[user.error].filter(notEmpty)} />;
//     }
//
//     const userCardInfo: IUserCardInfo = {
//         email: user.data.email,
//         userID: user.data.userID,
//         name: user.data.name,
//         photoUrl: user.data.photoUrl,
//         dateLastActive: user.data.dateLastActive || undefined,
//         dateJoined: user.data.dateInserted,
//         label: user.data.label,
//         countDiscussions: user.data.countDiscussions || 0,
//         countComments: user.data.countComments || 0,
//     };
//
//     return (
//         <PopupUserCard
//             visible={visible || false}
//             user={userCardInfo}
//             buttonContent={buttonContent}
//             openAsModal={openAsModal}
//         />
//     );
// }
//
// function UserCardModule(props: IProps) {
//     const { userID, buttonContent, openAsModal } = props;
//     const [ready, setReady] = useState(false);
//
//     return ready ? (
//         <UserCard visible={ready} userID={userID} buttonContent={buttonContent} openAsModal={openAsModal} />
//     ) : (
//         <PopupUserCard onClick={() => setReady(true)} buttonContent={buttonContent} openAsModal={openAsModal} />
//     );
// }
