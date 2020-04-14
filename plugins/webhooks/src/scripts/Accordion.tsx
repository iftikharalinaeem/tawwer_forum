/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";

interface IProps {
    isActive: boolean;
    isPeparing: boolean;
    isOpen: boolean;
    trigger: Element;
    customTag?: string;
}

export default function Accordion(props: IProps) {
    const { customTag } = props;
    return (
        <>
            <AccordionItem>
                <h3>
                    <AccordionButton>Step 1: Do a thing</AccordionButton>
                </h3>
                <AccordionPanel>
                    Integer ad iaculis semper aenean nibh quisque hac eget volutpat, at dui sem accumsan cras congue mi
                    varius egestas interdum, molestie blandit sociosqu sodales diam metus erat venenatis.
                </AccordionPanel>
            </AccordionItem>
        </>
    );
}
