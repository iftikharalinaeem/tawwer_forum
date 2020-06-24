/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Permission, { PermissionMode } from "@vanilla/library/src/scripts/features/users/Permission";
import { useKnowledgeBase } from "@knowledge/knowledge-bases/knowledgeBaseHooks";

interface IProps {
    kbID?: number | null;
    children: React.ReactNode;
    permission: string | string[];
    fallback?: React.ReactNode;
}

/**
 * Do a permission check for adding a knowledge base.
 */
export function KbPermission(props: IProps) {
    const { kbID } = props;
    const kb = useKnowledgeBase(kbID);

    if (props.kbID != null && !kb.data) {
        return <>{props.fallback}</>;
    }

    return (
        <Permission
            {...props}
            mode={kb.data?.hasCustomPermission ? PermissionMode.RESOURCE : PermissionMode.GLOBAL}
            resourceType="knowledgeBase"
            resourceID={kb.data?.hasCustomPermission ? kb.data.knowledgeBaseID : undefined}
        />
    );
}
