/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";

interface IProps {
    webhookID?: number;
    onClose: () => void;
}

export function WebhookAddEdit(props: IProps) {
    return (
        <form
            onSubmit={async event => {
                event.preventDefault();
            }}
        >
            form here
        </form>
    );
}
