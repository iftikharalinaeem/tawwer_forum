/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSelector } from "react-redux";
import { useEffect } from "react";
import { getCurrentLocale } from "@vanilla/i18n";

/**
 * Interface representing a webhook base resource.
 */
export interface IWebhook {
    status: string;
    events: [];
    name: string;
    url: string;
    secret: string;
}
