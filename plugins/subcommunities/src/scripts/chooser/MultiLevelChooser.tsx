/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LocaleChooser } from "@subcommunities/chooser/LocaleChooser";
import { ProductChooser } from "@subcommunities/chooser/ProductChooser";
import { getCurrentLocale } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";

export type SubcommunityChooserSection = "locale" | "product";

export function MultiLevelChooser(props: {
    activeSection: SubcommunityChooserSection;
    setActiveSection: (section: SubcommunityChooserSection) => void;
    activeSubcommunityID?: number;
}) {
    const { activeSection, setActiveSection, activeSubcommunityID } = props;
    const [selectedLocale, setSelectedLocale] = useState<string | null>(null);

    useEffect(() => {
        if (activeSection === "locale") {
            setSelectedLocale(null);
        }
    }, [activeSection, setSelectedLocale]);

    const [communityID] = useState<number | null>(null);

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
