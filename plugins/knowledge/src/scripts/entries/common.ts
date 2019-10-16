/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import rootReducer from "@knowledge/state/reducer";
import { serverReducer } from "@knowledge/server/serverReducer";
import NotificationsModel from "@library/features/notifications/NotificationsModel";
import ConversationsModel from "@library/features/conversations/ConversationsModel";
import { registerReducer } from "@library/redux/reducerRegistry";

registerReducer("knowledge", rootReducer);
registerReducer("notifications", new NotificationsModel().reducer);
registerReducer("conversations", new ConversationsModel().reducer);
registerReducer("server", serverReducer);
