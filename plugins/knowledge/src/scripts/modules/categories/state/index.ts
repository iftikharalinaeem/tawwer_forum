/**
 * A barrel file for the category state.
 *
 * Everything inside of this barrel should use relative paths.
 * Everything outside should use the barrel.
 *
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as constants from "./constants";
import reducer from "./reducer";
import * as model from "./model";

export * from "./types";
export { constants, reducer, model };
