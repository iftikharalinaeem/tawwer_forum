/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Tree, { IDragUpdate } from "@atlaskit/tree";

/**
 * Patch the atlaskit tree component to give us access to onDragUpdate.
 *
 * Note from @charrondev:
 * I'm trying to upstream this at the moment,
 * but am waiting to hear back from the the atlaskit team to get access to make a PR.
 * Access was request at https://ecosystem.atlassian.net/servicedesk/customer/portal/24
 */
export default class CombineAwareTree<T> extends Tree<T> {
    private originalDragUpdate;
    public constructor(props) {
        super(props);
        this.originalDragUpdate = this.onDragUpdate;
        this.onDragUpdate = this.customDragUpdate;
    }

    protected customDragUpdate = (update: IDragUpdate) => {
        this.props.onDragUpdate && this.props.onDragUpdate(update);
        this.originalDragUpdate(update);
    };
}
