/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

declare module "@atlaskit/tree" {
    import { DraggableStateSnapshot, DraggableProvided, DragUpdate } from "react-beautiful-dnd";
    export { DraggableStateSnapshot, DraggableProvided };

    export type Path = number[];

    export interface IFlattenedItem<T> {
        item: ITreeItem<T>;
        path: Path;
    }

    export interface ITreeSourcePosition {
        parentId: string;
        index: number;
    }

    export interface ITreeDestinationPosition {
        parentId: string;
        index?: number;
    }

    export function mutateTree<T>(tree: ITreeData<T>, id: string, data: Partial<ITreeItem<T>>);
    export function moveItemOnTree<T>(
        tree: ITreeData<T>,
        sourcePosition: ITreeSourcePosition,
        destinationPosition: ITreeDestinationPosition,
    );

    export interface ITreeData<T> {
        rootId: string;
        items: { [id: string]: ITreeItem<T> };
    }

    export interface ITreeItem<T> {
        id: string;
        parentID: string;
        children: string[];
        hasChildren?: boolean;
        isExpanded?: boolean;
        isChildrenLoading?: boolean;
        data: T;
    }

    export interface IRenderItemParams<T> {
        /** Item to be rendered */
        item: ITreeItem<T>;
        /** The depth of the item on the tree. 0 means root level. */
        depth: number;
        /** Function to call when a parent item needs to be expanded */
        onExpand: (itemId: string) => void;
        /** Function to call when a parent item needs to be collapsed */
        onCollapse: (itemId: string) => void;
        /** Couple of Props to be spread into the rendered React.Components and DOM elements */
        /** More info: https://github.com/atlassian/react-beautiful-dnd#children-function-render-props */
        provided: DraggableProvided;
        /** Couple of state variables */
        /** More info: https://github.com/atlassian/react-beautiful-dnd#2-snapshot-draggablestatesnapshot */
        snapshot: DraggableStateSnapshot;
    }

    export type IDragUpdate = DragUpdate;

    export interface ITreeProps<T> {
        tree: ITreeData<T>;
        onExpand?(itemID: string): void;
        onCollapse?(itemID: string): void;
        onDragStart?(source: ITreeSourcePosition): void;
        onDragUpdate?: (update: DragUpdate) => void;
        onDragEnd?(source: ITreeSourcePosition, destination?: ITreeDestinationPosition): void;
        renderItem(params: IRenderItemParams<T>): void;
        offsetPerLevel?: number;
        isDragEnabled?: boolean;
        isNestingEnabled?: boolean;
    }

    export default class Tree<T> extends React.Component<ITreeProps<T>> {
        protected onDragUpdate(update: IDragUpdate): void;
    }
}
