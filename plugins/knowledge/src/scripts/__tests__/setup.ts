/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { importAll } from "@library/__tests__/utility";

importAll((require as any).context("..", true, /.test.(ts|tsx)$/));
