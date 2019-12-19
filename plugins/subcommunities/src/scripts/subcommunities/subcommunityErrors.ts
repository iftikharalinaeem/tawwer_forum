/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IFieldError } from "@library/@types/api/core";

export function noSubcommunitiesFieldError(field: string = ""): IFieldError {
    return { field, code: "No subcommunities", message: t("This product has no subcommunities.") };
}
