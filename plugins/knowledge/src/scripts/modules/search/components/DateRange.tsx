/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import RadioButtonsAsTabs from "@library/components/radioButtonsAsTabs/radioButtonsAsTabs";
import RadioButtonTab from "@library/components/radioButtonsAsTabs/RadioButtonTab";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import Tokens from "@library/components/forms/select/Tokens";
import { dummyAuthors } from "@knowledge/modules/search/state/dummyAuthors";
import { IComboBoxOption } from "@library/components/forms/select/BigSearch";
import Checkbox from "@library/components/forms/Checkbox";
import {ISearchWithin} from "./AdvancedSearch";
import doNotRender from "@library/components/forms/select/overwrites/doNotRender";
import Select from "react-select";
import selectContainer from "@library/components/forms/select/overwrites/selectContainer";
import menu from "@library/components/forms/select/overwrites/menu";
import menuList from "@library/components/forms/select/overwrites/menuList";
import SelectOption from "@library/components/forms/select/overwrites/SelectOption";
import {dummyDateWithin} from "../state/dummyDateWithin";
import SelectOne from "@library/components/forms/select/SelectOne";

interface IProps {
    within: ISearchWithin;
    setWithin: (within: ISearchWithin) => void;
    of: string;
    setOf: (of: string) => void;
}

/**
 * Implements the DateRange component
 */
export default class DateRange extends React.PureComponent<IProps> {
    /**
     * Change handler for date within
     */
    private setWithin = (newValue: any) => {
        this.props.setWithin(newValue.data.within);
    };

    public render() {
        return (
            <div className="">
                <SelectOne label={t("Date Within")} className="" setOption={this.setWithin} options={dummyDateWithin} />
                <InputTextBlock label={t("Of")} onChange={this.handleWithinChange} value={this.props.title} />}
            of
        );
    }
}
