/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";

interface IProps {
    query: string;
    placeholder: string;
    clearQuery: () => void;
    setSearchQuery: (newQuery) => void;
}

/**
 * Implements the search bar component
 */
export default class SearchBar extends React.Component<IProps> {
    public render() {
        return "";
    }
}
