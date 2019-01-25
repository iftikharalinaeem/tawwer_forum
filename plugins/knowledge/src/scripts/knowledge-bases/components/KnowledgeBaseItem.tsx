/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import SmartLink from "@library/components/navigation/SmartLink";
import Paragraph from "@library/components/Paragraph";

interface IProps {
    iconUrl: string;
    title: string;
    description: string;
    url: string;
}

/**
 * Component representing a single knowledge base.
 */
export default function KnowledgeBaseItem(props: IProps) {
    return (
        <SmartLink to={props.url}>
            <img width="200" src={props.iconUrl} />
            <h2>{props.title}</h2>
            <Paragraph>{props.description}</Paragraph>
        </SmartLink>
    );
}
