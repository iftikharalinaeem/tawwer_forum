/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DownTriangleIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { FrameHeaderMinimal } from "@library/layout/frame/FrameHeaderMinimal";
import { chooserClasses } from "@subcommunities/chooser/chooserStyles";
import { LocaleChooser } from "@subcommunities/chooser/LocaleChooser";
import { ProductChooser } from "@subcommunities/chooser/ProductChooser";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import {
    useCurrentSubcommunity,
    useAvailableLocales,
    useLocaleInfo,
} from "@subcommunities/subcommunities/subcommunitySelectors";
import React, { useEffect, useRef, useState } from "react";
import classNames from "classnames";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { button } from "@storybook/addon-knobs";

type SectionName = "locale" | "product";

export function SubcommunityChooserDropdown() {
    const subcommunity = useCurrentSubcommunity();
    const [activeSection, setActiveSection] = useState<SectionName>("locale");
    const buttonRef = useRef<HTMLButtonElement>(null);
    const [isOpen, setIsOpen] = useState(false);
    const localeInfo = useLocaleInfo();

    if (!subcommunity || !localeInfo) {
        return null;
    }

    const classes = chooserClasses();
    const classesTitleBar = titleBarClasses();
    const classesTitleBarNav = titleBarNavClasses();

    let toggleName = subcommunity.name;
    if (localeInfo.count > 1) {
        toggleName += ` (${subcommunity.locale}) `;
    }

    return (
        <DropDown
            isVisible={isOpen}
            onVisibilityChange={setIsOpen}
            buttonRef={buttonRef}
            isSmall
            flyoutType={FlyoutType.FRAME}
            buttonBaseClass={ButtonTypes.TEXT}
            toggleButtonClassName={classNames(classesTitleBarNav.link, classesTitleBar.topElement)}
            renderLeft={true}
            buttonContents={
                <span className={classNames(classes.toggle, classesTitleBarNav.linkContent)}>
                    {toggleName}
                    <DownTriangleIcon className={classes.toggleArrow} />
                </span>
            }
        >
            <Frame
                header={
                    <FrameHeaderMinimal
                        onClose={() => {
                            setIsOpen(false);
                        }}
                    >
                        {localeInfo.count > 1 && (
                            <>
                                <Button
                                    baseClass={activeSection === "locale" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT}
                                    onClick={() => setActiveSection("locale")}
                                >
                                    Locales
                                </Button>
                                <hr className={classes.headingDivider} />
                            </>
                        )}
                        <Button
                            disabled={activeSection === "locale"}
                            baseClass={activeSection === "product" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT}
                            onClick={() => setActiveSection("product")}
                        >
                            Products
                        </Button>
                    </FrameHeaderMinimal>
                }
                body={
                    <FrameBody selfPadded className={classes.body}>
                        <SubcommunityChooser activeSection={activeSection} setActiveSection={setActiveSection} />
                    </FrameBody>
                }
                footer={null}
            />
        </DropDown>
    );
}

export function SubcommunityChooser(props: {
    activeSection: SectionName;
    setActiveSection: (section: SectionName) => void;
}) {
    const { activeSection, setActiveSection } = props;
    const subcommunity = useCurrentSubcommunity();
    const locales = useAvailableLocales();
    const [selectedLocale, setSelectedLocale] = useState<string | null>(subcommunity ? subcommunity.locale : null);
    const localeInfo = useLocaleInfo();
    useEffect(() => {
        if (localeInfo && localeInfo.defaultLocale) {
            setSelectedLocale(localeInfo.defaultLocale);
            props.setActiveSection("product");
        }
    }, [subcommunity]);

    const [communityID, setCommunityID] = useState<number | null>(null);

    if (localeInfo && localeInfo.count <= 1 && localeInfo.defaultLocale) {
        return <ProductChooser forLocale={localeInfo.defaultLocale} communityID={communityID} />;
    }

    if (activeSection === "product" && selectedLocale) {
        return (
            <ProductChooser
                forLocale={selectedLocale}
                communityID={communityID}
                onBack={() => {
                    setActiveSection("locale");
                }}
            />
        );
    } else {
        return (
            <LocaleChooser
                value={selectedLocale}
                onChange={newLocale => {
                    setSelectedLocale(newLocale);
                    setActiveSection("product");
                }}
            />
        );
    }
}
