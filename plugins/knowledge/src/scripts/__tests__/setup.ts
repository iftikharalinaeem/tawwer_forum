/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Enzyme from "enzyme";
import Adapter from "enzyme-adapter-react-16";
import { importAll } from "@library/__tests__/utility";

// Setup enzyme
Enzyme.configure({ adapter: new Adapter() });
importAll((require as any).context("..", true, /.test.(ts|tsx)$/));
