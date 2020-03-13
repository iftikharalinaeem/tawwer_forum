/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { useParams } from "react-router";

export default function PlaceHolderPage() {
    const params = useParams<{
        // Types of the params from your route match.
        // All parameters come from query so they will be strings.
        // Be sure to convert numbers/booleans/etc.
    }>();

    // Convert the params into numbers, booleans etc.

    return <>This page is a placeholder {JSON.stringify(params)}</>;
}
