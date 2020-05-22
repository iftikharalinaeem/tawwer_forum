/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LocaleChooser } from "@subcommunities/chooser/LocaleChooser";
import { ProductChooser } from "@subcommunities/chooser/ProductChooser";
import { useAvailableSubcommunityLocales } from "@subcommunities/subcommunities/subcommunitySelectors";
import { getCurrentLocale, useLocaleInfo } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";

export type SubcommunityChooserSection = "locale" | "product";

export function SubcommunityChooser(props: {
    activeSection: SubcommunityChooserSection;
    setActiveSection: (section: SubcommunityChooserSection) => void;
    activeSubcommunityID?: number;
}) {
    const { activeSection, setActiveSection, activeSubcommunityID } = props;
    const [selectedLocale, setSelectedLocale] = useState<string | null>(null);
    const availableLocales = useAvailableSubcommunityLocales();

    useEffect(() => {
        if (activeSection === "locale") {
            setSelectedLocale(null);
        }
    }, [activeSection, setSelectedLocale]);

    const { currentLocale } = useLocaleInfo();
    const [communityID] = useState<number | null>(null);

    if (availableLocales && Object.values(availableLocales).length <= 1 && currentLocale) {
        return <ProductChooser forLocale={currentLocale} communityID={communityID} />;
    }

    if (activeSection === "product") {
        return (
            <ProductChooser
                activeSubcommunityID={activeSubcommunityID}
                forLocale={selectedLocale ?? getCurrentLocale()}
                communityID={communityID}
                onBack={
                    selectedLocale
                        ? () => {
                              setActiveSection("locale");
                              setSelectedLocale(null);
                          }
                        : undefined
                }
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
