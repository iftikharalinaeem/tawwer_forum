/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LocaleChooser } from "@subcommunities/chooser/LocaleChooser";
import { ProductChooser } from "@subcommunities/chooser/ProductChooser";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { useLocaleInfo } from "@subcommunities/products/productSelectors";
import React, { useState, useEffect, useReducer, useRef } from "react";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { useCurrentSubcommunity } from "@subcommunities/subcommunities/subcommunitySelectors";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DownTriangleIcon } from "@library/icons/common";
import { chooserClasses } from "@subcommunities/chooser/chooserStyles";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Frame from "@library/layout/frame/Frame";
import { MinimalFrameHeader } from "@library/layout/frame/MinimalFrameHeader";
import Loader from "@library/loaders/Loader";

type SectionName = "locale" | "product";

export function SubcommunityChooserDropdown() {
    const subcommunity = useCurrentSubcommunity();
    const [activeSection, setActiveSection] = useState<SectionName>("locale");
    const buttonRef = useRef<HTMLButtonElement>(null);

    if (!subcommunity) {
        return <Loader />;
    }

    const classes = chooserClasses();

    return (
        <DropDown
            buttonRef={buttonRef}
            isSmall
            flyoutType={FlyoutType.FRAME}
            buttonBaseClass={ButtonTypes.TEXT}
            renderLeft={true}
            buttonContents={
                <span className={classes.toggle}>
                    {subcommunity.name + ` (${subcommunity.locale}) `}
                    <DownTriangleIcon className={classes.toggleArrow} />
                </span>
            }
        >
            <Frame
                header={
                    <MinimalFrameHeader
                        onClose={() => {
                            document.body.focus();
                            buttonRef.current && buttonRef.current.focus();
                        }}
                    >
                        <Button
                            baseClass={activeSection === "locale" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT}
                            onClick={() => setActiveSection("locale")}
                        >
                            Locales
                        </Button>
                        <hr className={classes.headingDivider} />
                        <Button
                            baseClass={activeSection === "product" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT}
                            onClick={() => setActiveSection("product")}
                        >
                            Products
                        </Button>
                    </MinimalFrameHeader>
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
    const context = useCommunityFilterContext();
    const [selectedLocale, setSelectedLocale] = useState<string | null>(subcommunity ? subcommunity.locale : null);
    useEffect(() => {
        if (subcommunity) {
            setSelectedLocale(subcommunity.locale);
        }
    }, [subcommunity]);

    const [communityID, setCommunityID] = useState<number | null>(null);
    const localeInfo = useLocaleInfo();

    if (localeInfo && localeInfo.count <= 1 && localeInfo.defaultLocale) {
        return (
            <ProductChooser
                forLocale={localeInfo.defaultLocale}
                hideNoProductCommunities={context.hideNoProductCommunities}
                communityID={communityID}
                linkSuffix={context.linkSuffix}
            />
        );
    }

    if (activeSection === "product" && selectedLocale) {
        return (
            <ProductChooser
                linkSuffix={context.linkSuffix}
                hideNoProductCommunities={context.hideNoProductCommunities}
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
