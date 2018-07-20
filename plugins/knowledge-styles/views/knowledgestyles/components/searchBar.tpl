<div class="_searchBar" role="search">
    <form class="_searchBar-form" action="" method="post">
        <div class="_searchBar-bar" role="combobox" aria-haspopup="true" aria-owns="[id of results]" aria-expanded="false" aria-activedescendant="[id of active descendant]">
            <label class="_searchBar-inputAndIcon">
                <input class="inputText _searchBar-input" type="search" role="searchbox" aria-autocomplete="list" aria-owns="[id of results]" placeholder="Type your search here">
                <svg class="_icon _searchBar-searchIcon" viewBox="0 0 24 24">
                    <title>Search</title>
                    <path fill="currentColor" d="M13.2,16C9.5,18,4.8,16.8,2.7,13.1S2,4.8,5.6,2.7S14,1.9,16,5.5c0.7,1.2,1,2.4,1,3.8c0,1.4-0.4,2.7-1.1,3.9l0.5,0.5l5.3,5.3c0.7,0.7,0.7,1.8,0,2.5l-0.3,0.3c-0.7,0.7-1.8,0.7-2.5,0c0,0,0,0,0,0l-5.3-5.3L13.2,16L13.2,16z M9.3,16C13,16,16,13,16,9.3c0-3.7-3-6.7-6.7-6.7c-3.7,0-6.7,3-6.7,6.7C2.6,13,5.6,16,9.3,16C9.3,16,9.3,16,9.3,16z M9.3,15.1c-3.2,0-5.8-2.6-5.7-5.8s2.6-5.8,5.8-5.7c3.2,0,5.7,2.6,5.7,5.8C15.1,12.5,12.5,15.1,9.3,15.1C9.3,15.1,9.3,15.1,9.3,15.1z M9.3,14.1c2.7,0,4.8-2.2,4.8-4.8c0-2.7-2.2-4.8-4.8-4.8c-2.6,0-4.8,2.2-4.8,4.8C4.5,12,6.7,14.1,9.3,14.1C9.3,14.1,9.3,14.1,9.3,14.1L9.3,14.1z M14.4,15.8l5.3,5.3c0.3,0.3,0.8,0.3,1.1,0l0.3-0.3c0.3-0.3,0.3-0.8,0-1.1l-5.3-5.3L14.4,15.8L14.4,15.8z"/>
                </svg>
            </label>
            <button type="submit" class="_button _searchBar-button _button-callToAction">
                Search
            </button>
            {*<button type="button" aria-label="Clear Search Field" class="_button _searchBar-clearSearch">*}
                {*<svg class="_icon button-closeIcon" aria-hidden="true" viewBox="0 0 24 24">*}
                    {*<title>{t c="Close"}</title>*}
                    {*<path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>*}
                {*</svg>*}
            {*</button>*}

            <div class="_autoComplete">
                <ul class="_autoComplete-results" role="listbox" aria-label="AutoComplete Suggestions">
                    {include "autoComplete-result.tpl" title="Getting Installation Help Help With your Community How to get Help if Everything Else Fails Help Help With your Last Edited June 20th 2018 Community How to get Help 20 December 2015 Article by Todd Burry" metas=["Article by Todd Burry", "20 December 2015", "Lorem Ipsum", "Last Edited June 20th 2018"] word="true" excel="true" pdf="true" selected="true"}
                    {include "autoComplete-result.tpl" title="Getting Help With your Community" metas=["Article by Todd Burry", "20 December 2015", "Lorem Ipsum", "Last Edited June 20th 2018"] word="true" excel="true" pdf="true"}
                    {include "autoComplete-result.tpl" title="Theming Help" metas=["Article by Todd Burry", "20 December 2015", "Lorem Ipsum", "Last Edited June 20th 2018"] word="true" pdf="true"}
                    {include "autoComplete-result.tpl" title="How to get Help if Everything Else Fails" metas=["Article by Todd Burry", "20 December 2015", "Lorem Ipsum", "Last Edited June 20th 2018"]}
                    {include "autoComplete-result.tpl" title="Popular Help Documents and Videos" metas=["Article by Todd Burry", "20 December 2015", "Lorem Ipsum", "Last Edited June 20th 2018"] pdf="true"}
                    {include "autoComplete-result.tpl" title="Short" metas=["Short"]}
                    {include "autoComplete-result.tpl" title="Installation Help" metas=["Article by Todd Burry", "20 December 2015", "Lorem Ipsum", "Last Edited June 20th 2018"] word="true" excel="true" pdf="true"}
                    {include "autoComplete-result.tpl" title="Overflowing title, title too long to be contained on one line of text in the search result" metas=["Article by Todd Burry", "20 December 2015", "Lorem Ipsum", "Last Edited June 20th 2018"] word="true" pdf="true"}
                </ul>
            </div>
        </div>
    {if $advanced|default:'false' === "true"}
        <div id="advancedSearchFields" role="region" aria-expanded="false" class="_searchBarAdvanced">
            <div class="_searchBarAdvanced-contents">
                <h2 class="_searchBarAdvanced-title sr-only" tabindex="-1">
                    Advanced Search Fields
                </h2>
                <fieldset class="inputBlock radioButtonsAsTabs _searchBarAdvanced-searchIn">
                    <legend class="sr-only">Search in:</legend>
                    <div class="radioButtonsAsTabs-tabs">
                        <label class="radioButtonsAsTabs-tab">
                            <input class="radioButtonsAsTabs-input" type="radio" name="AdvancedSearchType" value="Articles" checked="checked">
                            <span class="radioButtonsAsTabs-label">Articles</span>
                        </label>
                        <label class="radioButtonsAsTabs-tab">
                            <input class="radioButtonsAsTabs-input" type="radio" name="AdvancedSearchType" value="Everywhere">
                            <span class="radioButtonsAsTabs-label">Everywhere</span>
                        </label>
                    </div>
                </fieldset>

                <label class="inputBlock">
                    <span class="inputBlock-labelAndDescription">
                        <span class="inputBlock-labelText">
                            Title
                        </span>
                        {*<Paragraph class="inputBlock-labelNote" content={this.props.labelNote} />*}
                    </span>
                    <span class="inputBlock-inputWrap">
                        <input class="inputBlock-input inputText" type="text"/>
                    </span>
                </label>

                <label class="inputBlock">
                    <span class="inputBlock-labelAndDescription">
                        <span class="inputBlock-labelText">
                            Author
                        </span>
                        {*<Paragraph class="inputBlock-labelNote" content={this.props.labelNote} />*}
                    </span>
                    <span class="inputBlock-inputWrap">
                        <input class="inputBlock-input inputText" type="text"/>
                    </span>
                </label>
                <label class="inputBlock">
                    <span class="inputBlock-labelAndDescription">
                        <span class="inputBlock-labelText">
                            File Name
                        </span>
                        {*<Paragraph class="inputBlock-labelNote" content={this.props.labelNote} />*}
                    </span>
                    <span class="inputBlock-inputWrap">
                        <input class="inputBlock-input inputText" type="text"/>
                    </span>
                </label>
                <div class="inputBlock">
                    <span class="inputBlock-labelAndDescription">
                        <span class="inputBlock-labelText">
                            Date Within
                        </span>
                    </span>
                    <div class="inputBlock-miniInputs">
                        <span class="inputBlock-inputWrap inputBlock-miniInput">
                            <input class="inputBlock-input inputText" type="text"/>
                        </span>

                        <span class="inputBlock-inputWrap inputBlock-inlineLabelWrap">
                            <span class="inputBlock-inlineLabel">
                                of
                            </span>
                        </span>

                        <span class="inputBlock-inputWrap inputBlock-miniInput">
                            <input class="inputBlock-input inputText" type="text"/>
                        </span>
                    </div>
                </div>
            </div>
            <div class="_buttonGroup">
                <button type="button" class="_searchBar-cancel _button">
                    <span class="_searchBarAdvanced-buttonText">Cancel</span>
                </button>

                <button type="submit" class="_searchBar-submit _button _button-callToAction">
                    <span class="_searchBarAdvanced-buttonText">Search</span>
                </button>
            </div>
        </div>
        <div class="_searchBarAdvanced_footer">
            <div class="_searchBarAdvanced-query">
                <span class="_meta">Author: Todd, Dan</span>
                <span class="_meta">Within 1 day of today</span>
            </div>
            <button id="toggleAdvanced" aria-controls="advancedSearchFields" type="button" class="_searchBar-toggleAdvanced _button-fakeLink" aria-pressed="false">
                <span class="_searchBarAdvanced-buttonText">Advanced</span>
            </button>
        </div>
    {/if}
    </form>
</div>
