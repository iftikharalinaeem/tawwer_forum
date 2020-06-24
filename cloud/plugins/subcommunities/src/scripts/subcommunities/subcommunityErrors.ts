/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IFieldError } from "@library/@types/api/core";
import { t } from "@vanilla/i18n";

export function noSubcommunitiesFieldError(field: string = ""): IFieldError {
    return { field, code: "No subcommunities", message: t("This product has no subcommunities.") };
}
