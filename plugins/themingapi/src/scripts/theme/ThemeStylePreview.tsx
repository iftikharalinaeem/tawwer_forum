/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import { BrowserRouter, RouteComponentProps, useHistory } from "react-router-dom";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";

export default function ThemeStylePreview() {
    console.log("in page");
    return (
        <BrowserRouter>
            <Modal isVisible={true} scrollable={true} size={ModalSizes.FULL_SCREEN}>
                <div>Hello</div>
            </Modal>
        </BrowserRouter>
    );
}
