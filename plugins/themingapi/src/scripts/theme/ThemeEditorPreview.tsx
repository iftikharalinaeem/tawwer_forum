/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect, useRef, useState } from "react";
import { BrowserRouter, RouteComponentProps, useHistory, MemoryRouter } from "react-router-dom";
import TitleBar from "@library/headers/TitleBar";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import Container from "@library/layout/components/Container";
import Paragraph from "@vanilla/library/src/scripts/layout/Paragraph";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import Button from "@vanilla/library/src/scripts/forms/Button";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { LinkContext } from "@vanilla/library/src/scripts/routing/links/LinkContextProvider";
import themeEditorPreviewClasses from "./themeEditorPreviewStyles";
import { useOwnFrameMessages } from "@themingapi/theme/IframeCommunicationContext";
import { logDebug } from "@vanilla/utils";

export default function ThemeStylePreview() {
    const [intialInputValue, newInputValue] = useState("Text Input");
    const classes = themeEditorPreviewClasses();

    useEffect(() => {
        document.body.classList.add(classes.contentContainer);
    });

    const onFrame = useCallback(message => {
        // console.log("Recieved message in frame", message);
    }, []);

    useOwnFrameMessages(onFrame);

    return (
        <MemoryRouter>
            <LinkContext.Provider
                value={{
                    linkContext: "",
                    isDynamicNavigation: () => {
                        return true;
                    },
                    pushSmartLocation: () => {},
                    makeHref: () => {
                        return "";
                    },
                }}
            >
                <TitleBar />
                <Banner title="Welcome To Your Theme" />
                <Container>
                    <div className={classes.content}>
                        <Paragraph className={classes.description}>
                            This is a style guide of your theme. It has examples of the visual elements used throught
                            the application. You can click on the various widgets such as the menu or hero to edit their
                            properties in the side panel. In addion to the widgets there are also global styles. To edit
                            global styles click anywhere else on the page, such as this text.
                        </Paragraph>

                        <div className={classes.buttonStyles}>
                            <h2 className={classes.title}>Buttons</h2>
                            <p>There are two types of buttons in the application: primary and secondary.</p>
                            <div className={classes.styleContent}>
                                <Button baseClass={ButtonTypes.PRIMARY}>Primary Button</Button>
                                <Button>Secondary Button</Button>{" "}
                            </div>
                        </div>
                        <div className={classes.inputStyles}>
                            <h2 className={classes.title}>Inputs</h2>
                            <p>User inputs are based on the global background and text colors.</p>
                            <div className={classes.styleContent}>
                                <InputTextBlock
                                    inputProps={{
                                        value: intialInputValue,
                                        placeholder: "Text Input",
                                        onChange: event => {
                                            newInputValue(event.target.value);
                                        },
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </Container>
            </LinkContext.Provider>
        </MemoryRouter>
    );
}
