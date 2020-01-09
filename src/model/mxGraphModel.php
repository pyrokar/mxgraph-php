<?php

declare(strict_types=1);

namespace MxGraph;

use Exception;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxGraphModel extends mxEventSource
{
    /**
     * Class: mxGraphModel.
     *
     * Cells are the elements of the graph model. They represent the state
     * of the groups, vertices and edges in a graph.
     *
     * Fires a graphModelChanged event after each group of changes.
     *
     * Variable: root
     *
     * Holds the root cell, which in turn contains the cells that represent the
     * layers of the diagram as child cells. That is, the actual elements of the
     * diagram are supposed to live in the third generation of cells and below.
     *
     * @var mxCell | null
     */
    public $root;

    /**
     * Variable: cells.
     *
     * Maps from Ids to cells.
     *
     * @var array<int, mxCell>
     */
    public $cells;

    /**
     * Variable: maintainEdgeParent.
     *
     * Specifies if edges should automatically be moved into the nearest common
     * ancestor of their terminals. Default is true.
     *
     * @var bool
     */
    public $maintainEdgeParent = true;

    /**
     * Variable: createIds.
     *
     * Specifies if the model should automatically create Ids for new cells.
     * Default is true.
     *
     * @var bool
     */
    public $createIds = true;

    /**
     * Variable: nextId.
     *
     * Specifies the next Id to be created. Default is 0.
     *
     * @var int
     */
    public $nextId = 0;

    /**
     * Variable: updateLevel.
     *
     * Counter for the depth of nested transactions. Each call to <beginUpdate>
     * will increment this number and each call to <endUpdate> will decrement
     * it. When the counter reaches 0, the transaction is closed and the
     * respective events are fired. Initial value is 0.
     *
     * @var int
     */
    public $updateLevel = 0;

    /**
     * Constructor: mxGraphModel.
     *
     * Constructs a new graph model using the specified root cell.
     *
     * @param mxCell $root
     *
     * @throws Exception
     */
    public function __construct(mxCell $root = null)
    {
        if ($root) {
            $this->setRoot($root);
        } else {
            $this->clear();
        }
    }

    /**
     * Function: clear.
     *
     * Sets a new root using <createRoot>.
     */
    public function clear(): void
    {
        $this->setRoot($this->createRoot());
    }

    /**
     * Function: createRoot.
     *
     * Creates a new root cell with a default layer (child 0).
     */
    public function createRoot(): mxCell
    {
        $root = new mxCell();
        $root->insert(new mxCell());

        return $root;
    }

    /**
     * Function: getCells.
     *
     * Returns the internal lookup table that is used to map from Ids to cells.
     *
     * @return array<mxCell>
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * Function: getCell.
     *
     * @param int $id
     *
     * @return mxCell
     */
    public function getCell(int $id): ?mxCell
    {
        return $this->cells[$id] ?? null;
    }

    /**
     * Function: getRoot.
     *
     * Returns the root of the model.
     */
    public function getRoot(): mxCell
    {
        return $this->root;
    }

    /**
     * Function: setRoot.
     *
     * Sets the <root> of the model using <mxRootChange> and adds the change to
     * the current transaction. This resets all datastructures in the model and
     * is the preferred way of clearing an existing model. Returns the new
     * root.
     *
     * Parameters:
     *
     * root - <mxCell> that specifies the new root.
     *
     * @param mxCell $root
     *
     * @throws Exception
     *
     * @return mxCell | null
     */
    public function setRoot(mxCell $root = null): ?mxCell
    {
        $oldRoot = $this->root;

        $this->beginUpdate();

        try {
            $this->root = $root;
            $this->nextId = 0;
            $this->cells = [];

            $this->cellAdded($root);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        return $oldRoot;
    }

    //
    // Cell Cloning
    //

    /**
     * Function: cloneCell.
     *
     * Returns a deep clone of the given <mxCell> (including
     * the children) which is created using <cloneCells>.
     *
     * Parameters:
     *
     * cell - <mxCell> to be cloned.
     *
     * @param mxCell $cell
     *
     * @return mxCell
     */
    public function cloneCell($cell): mxCell
    {
        $clones = $this->cloneCells([$cell], true);

        return $clones[0];
    }

    /**
     * Function: cloneCells.
     *
     * Returns an array of clones for the given array of <mxCells>.
     * Depending on the value of includeChildren, a deep clone is created for
     * each cell. Connections are restored based if the corresponding
     * cell is contained in the passed in array.
     *
     * Parameters:
     *
     * cells - Array of <mxCell> to be cloned.
     * includeChildren - Boolean indicating if the cells should be cloned
     * with all descendants.
     *
     * @param array<mxCell> $cells
     * @param bool          $includeChildren
     *
     * @return array<mxCell>
     */
    public function cloneCells($cells, $includeChildren = true): array
    {
        $mapping = [];
        $clones = [];

        foreach ($cells as $cell) {
            $clne = $this->cloneCellImpl($cell, $mapping, $includeChildren);
            $clones[] = $clne;
        }

        for ($i = 0, $iMax = count($clones); $i < $iMax; ++$i) {
            $this->restoreClone($clones[$i], $cells[$i], $mapping);
        }

        return $clones;
    }

    /**
     * Function: cloneCellImpl.
     *
     * Inner helper method for cloning cells recursively.
     *
     * @param mxCell                $cell
     * @param array<string, mxCell> $mapping
     * @param bool                  $includeChildren
     *
     * @return mxCell
     */
    public function cloneCellImpl(mxCell $cell, $mapping, $includeChildren): mxCell
    {
        $ident = mxCellPath::create($cell);

        if (isset($mapping[$ident])) {
            return $mapping[$ident];
        }

        $clone = $this->cellCloned($cell);
        $mapping[$ident] = $clone;

        if ($includeChildren) {
            $childCount = $this->getChildCount($cell);

            for ($i = 0; $i < $childCount; ++$i) {
                $child = $this->getChildAt($cell, $i);
                $cloneChild = $this->cloneCellImpl($child, $mapping, true);
                $clone->insert($cloneChild);
            }
        }

        return $clone;
    }

    /**
     * Function: cellCloned.
     *
     * Hook for cloning the cell. This returns cell->copy() or
     * any possible exceptions.
     *
     * @param mxCell $cell
     *
     * @return mxCell
     */
    public function cellCloned($cell): mxCell
    {
        return $cell->copy();
    }

    /**
     * Function: restoreClone.
     *
     * Inner helper method for restoring the connections in
     * a network of cloned cells.
     *
     * @param mxCell                $clone
     * @param mxCell                $cell
     * @param array<string, mxCell> $mapping
     */
    public function restoreClone(mxCell $clone, mxCell $cell, array $mapping): void
    {
        $source = $this->getTerminal($cell, true);

        if (null != $source) {
            $tmp = $mapping[mxCellPath::create($source)];

            if (null != $tmp) {
                $tmp->insertEdge($clone, true);
            }
        }

        $target = $this->getTerminal($cell, false);

        if (null != $target) {
            $tmp = $mapping[mxCellPath::create($target)];

            if (null != $tmp) {
                $tmp->insertEdge($clone, false);
            }
        }

        $childCount = $this->getChildCount($clone);

        for ($i = 0; $i < $childCount; ++$i) {
            $this->restoreClone(
                $this->getChildAt($clone, $i),
                $this->getChildAt($cell, $i),
                $mapping
            );
        }
    }

    /**
     * Function: isAncestor.
     *
     * Returns true if the given parent is an ancestor of the given child.
     *
     * Parameters:
     *
     * parent - <mxCell> that specifies the parent.
     * child - <mxCell> that specifies the child.
     *
     * @param mixed $parent
     * @param mixed $child
     *
     * @return bool
     */
    public function isAncestor($parent, $child): bool
    {
        while (null != $child && $child != $parent) {
            $child = $this->getParent($child);
        }

        return $child === $parent;
    }

    /**
     * Function: contains.
     *
     * Returns true if the model contains the given <mxCell>.
     *
     * Parameters:
     *
     * cell - <mxCell> that specifies the cell.
     *
     * @param mixed $cell
     *
     * @return bool
     */
    public function contains($cell): bool
    {
        return $this->isAncestor($this->root, $cell);
    }

    /**
     * Function: getParent.
     *
     * Returns the parent of the given cell.
     *
     * Parameters:
     *
     * cell - <mxCell> whose parent should be returned.
     *
     * @param mxCell $cell
     *
     * @return mxCell
     */
    public function getParent(mxCell $cell): ?mxCell
    {
        return $cell->getParent();
    }

    /**
     * Function: add.
     *
     * Adds the specified child to the parent at the given index using
     * <mxChildChange> and adds the change to the current transaction. If no
     * index is specified then the child is appended to the parent's array of
     * children. Returns the inserted child.
     *
     * Parameters:
     *
     * parent - <mxCell> that specifies the parent to contain the child.
     * child - <mxCell> that specifies the child to be inserted.
     * index - Optional integer that specifies the index of the child.
     *
     * @param mixed      $parent
     * @param mixed      $child
     * @param null|mixed $index
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function add($parent, $child, $index = null)
    {
        if ($child !== $parent && null != $child && null != $parent) {
            $parentChanged = $parent !== $this->getParent($child);

            $this->beginUpdate();

            try {
                $parent->insert($child, $index);
                $this->cellAdded($child);
            } catch (Exception $e) {
                $this->endUpdate();

                throw($e);
            }
            $this->endUpdate();

            if ($parentChanged && $this->maintainEdgeParent) {
                $this->updateEdgeParents($child);
            }
        }

        return $child;
    }

    /**
     * Function: cellAdded.
     *
     * Inner callback to update <cells> when a cell has been added. This
     * implementation resolves collisions by creating new Ids.
     *
     * Parameters:
     *
     * cell - <mxCell> that specifies the cell that has been added.
     *
     * @param mxCell $cell
     */
    public function cellAdded(mxCell $cell = null): void
    {
        if (!$cell) {
            return;
        }

        if ($this->createIds && null === $cell->getId()) {
            $cell->setId($this->createId($cell));
        }

        if (null !== $cell->getId()) {
            $collision = $this->getCell($cell->getId());

            if ($collision !== $cell) {
                while (null !== $collision) {
                    $cell->setId($this->createId($cell));
                    $collision = $this->getCell($cell->getId());
                }

                $this->cells[$cell->getId()] = $cell;
            }
        }

        // Makes sure IDs of deleted cells are not reused
        if (is_numeric($cell->getId())) {
            $this->nextId = max($this->nextId, $cell->getId() + 1);
        }

        $childCount = $this->getChildCount($cell);

        for ($i = 0; $i < $childCount; ++$i) {
            $this->cellAdded($this->getChildAt($cell, $i));
        }
    }

    /**
     * Function: createId.
     *
     * Hook method to create an Id for the specified cell. This
     * implementation increments <nextId>.
     *
     * Parameters:
     *
     * cell - <mxCell> to create the Id for.
     *
     * @param mixed $cell
     *
     * @return int
     */
    public function createId($cell): int
    {
        $id = $this->nextId;
        ++$this->nextId;

        return $id;
    }

    /**
     * Function: updateEdgeParents.
     *
     * Updates the parent for all edges that are connected to cell or one of
     * its descendants using <updateEdgeParent>.
     *
     * @param mxCell $cell
     * @param mxCell $root
     *
     * @throws Exception
     */
    public function updateEdgeParents(mxCell $cell, mxCell $root = null): void
    {
        // Gets the topmost node of the hierarchy
        $root = $root ?? $this->getRoot();

        // Updates edges on children first
        $childCount = $this->getChildCount($cell);

        for ($i = 0; $i < $childCount; ++$i) {
            $child = $this->getChildAt($cell, $i);
            $this->updateEdgeParents($child, $root);
        }

        // Updates the parents of all connected edges
        $edgeCount = $this->getEdgeCount($cell);
        $edges = [];

        for ($i = 0; $i < $edgeCount; ++$i) {
            $edges[] = $this->getEdgeAt($cell, $i);
        }

        foreach ($edges as $edge) {
            // Updates edge parent if edge and child have
            // a common root node (does not need to be the
            // model root node)
            if ($this->isAncestor($root, $edge)) {
                $this->updateEdgeParent($edge, $root);
            }
        }
    }

    /**
     * Function: updateEdgeParent.
     *
     * Inner callback to update the parent of the specified <mxCell> to the
     * nearest-common-ancestor of its two terminals.
     *
     * Parameters:
     *
     * edge - <mxCell> that specifies the edge.
     * root - <mxCell> that represents the current root of the model.
     *
     * @param mixed $edge
     * @param mixed $root
     *
     * @throws Exception
     */
    public function updateEdgeParent($edge, $root): void
    {
        $source = $this->getTerminal($edge, true);
        $target = $this->getTerminal($edge, false);
        $cell = null;

        // Uses the first non-relative descendants of the source terminal
        while (null != $source && !$this->isEdge($source) &&
            null != $source->geometry && $source->geometry->relative) {
            $source = $this->getParent($source);
        }

        // Uses the first non-relative descendants of the target terminal
        while (null != $target && !$this->isEdge($target) &&
            null != $target->geometry && $target->geometry->relative) {
            $target = $this->getParent($target);
        }

        if ($this->isAncestor($root, $source) &&
            $this->isAncestor($root, $target)) {
            if ($source === $target) {
                $cell = $this->getParent($source);
            } else {
                $cell = $this->getNearestCommonAncestor($source, $target);
            }

            if (null != $cell &&
                $this->getParent($cell) !== $this->root &&
                $this->getParent($edge) !== $cell) {
                $geo = $this->getGeometry($edge);

                if (null != $geo) {
                    $origin1 = $this->getOrigin($this->getParent($edge));
                    $origin2 = $this->getOrigin($cell);

                    $dx = $origin2->x - $origin1->x;
                    $dy = $origin2->y - $origin1->y;

                    $geo = $geo->copy();
                    $geo->translate(-$dx, -$dy);
                    $this->setGeometry($edge, $geo);
                }

                $this->add($cell, $edge, $this->getChildCount($cell));
            }
        }
    }

    /**
     * Function: getOrigin.
     *
     * Returns the absolute, cummulated origin for the children inside the
     * given parent as an <mxPoint>.
     *
     * @param mixed $cell
     *
     * @return null|mxPoint
     */
    public function getOrigin($cell): ?mxPoint
    {
        $result = null;

        if (null != $cell) {
            $result = $this->getOrigin($this->getParent($cell));

            if (!$this->isEdge($cell)) {
                $geo = $this->getGeometry($cell);

                if (null != $geo) {
                    $result->x += $geo->x;
                    $result->y += $geo->y;
                }
            }
        } else {
            $result = new mxPoint();
        }

        return $result;
    }

    /**
     * Function: getNearestCommonAncestor.
     *
     * Returns the nearest common ancestor for the specified cells.
     *
     * Parameters:
     *
     * cell1 - <mxCell> that specifies the first cell in the tree.
     * cell2 - <mxCell> that specifies the second cell in the tree.
     *
     * @param mxCell $cell1
     * @param mxCell $cell2
     *
     * @return mxCell
     */
    public function getNearestCommonAncestor(mxCell $cell1, mxCell $cell2): ?mxCell
    {
        // Creates the cell path for the second cell
        $path = mxCellPath::create($cell2);

        if ('' !== $path) {
            // Bubbles through the ancestors of the target
            // cell to find the nearest common ancestor.
            $cell = $cell1;
            $current = mxCellPath::create($cell);

            while ($cell) {
                $parent = $this->getParent($cell);

                // Checks if the cell path is equal to the beginning
                // of the given cell path
                if (0 === strpos($path, $current.mxCellPath::$PATH_SEPARATOR) && $parent) {
                    return $cell;
                }

                $current = mxCellPath::getParentPath($current);
                $cell = $parent;
            }
        }

        return null;
    }

    /**
     * Function: remove.
     *
     * Removes the specified cell from the model using <mxChildChange> and adds
     * the change to the current transaction. This operation will remove the
     * cell and all of its children from the model. Returns the removed cell.
     *
     * Parameters:
     *
     * cell - <mxCell> that should be removed.
     *
     * @param mixed $cell
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function remove($cell)
    {
        $this->beginUpdate();

        try {
            if ($cell === $this->root) {
                $this->setRoot();
            } else {
                $cell->removeFromParent();
            }

            $this->cellRemoved($cell);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        return $cell;
    }

    /**
     * Function: cellRemoved.
     *
     * Inner callback to update <cells> when a cell has been removed.
     *
     * Parameters:
     *
     * cell - <mxCell> that specifies the cell that has been removed.
     *
     * @param mixed $cell
     */
    public function cellRemoved($cell): void
    {
        if (null != $cell) {
            $childCount = $this->getChildCount($cell);

            for ($i = 0; $i < $childCount; ++$i) {
                $this->cellRemoved($this->getChildAt($cell, $i));
            }

            $cell->removeFromTerminal(true);
            $cell->removeFromTerminal(false);

            if (null != $this->cells && null != $cell->getId()) {
                unset($this->cells[$cell->getId()]);
            }
        }
    }

    /**
     * Function: getChildCount.
     *
     * Returns the number of children in the given cell.
     *
     * Parameters:
     *
     * cell - <mxCell> whose number of children should be returned.
     *
     * @param mixed $cell
     *
     * @return int
     */
    public function getChildCount($cell): int
    {
        return (null != $cell) ? $cell->getChildCount() : 0;
    }

    /**
     * Function: getChildAt.
     *
     * Returns the child of the given <mxCell> at the given index.
     *
     * Parameters:
     *
     * cell - <mxCell> that represents the parent.
     * index - Integer that specifies the index of the child to be returned.
     *
     * @param mxCell $cell
     * @param int    $index
     *
     * @return mxCell | null
     */
    public function getChildAt(mxCell $cell, int $index): ?mxCell
    {
        return $cell->getChildAt($index);
    }

    /**
     * Function: getTerminal.
     *
     * Returns the source or target <mxCell> of the given edge depending on the
     * value of the boolean parameter.
     *
     * Parameters:
     *
     * edge - <mxCell> that specifies the edge.
     * source - Boolean indicating which end of the edge should be returned.
     *
     * @param mxCell $edge
     * @param bool   $source
     *
     * @return mxCell | null
     */
    public function getTerminal(mxCell $edge, bool $source): ?mxCell
    {
        return $edge->getTerminal($source);
    }

    /**
     * Function: setTerminal.
     *
     * Sets the source or target terminal of the given <mxCell> using
     * <mxTerminalChange> and adds the change to the current transaction.
     * This implementation updates the parent of the edge using <updateEdgeParent>
     * if required.
     *
     * Parameters:
     *
     * edge - <mxCell> that specifies the edge.
     * terminal - <mxCell> that specifies the new terminal.
     * isSource - Boolean indicating if the terminal is the new source or
     * target terminal of the edge.
     *
     * @param mixed $edge
     * @param mixed $terminal
     * @param mixed $source
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function setTerminal($edge, $terminal, $source)
    {
        $previous = $edge->getTerminal($source);

        $this->beginUpdate();

        try {
            if (null != $terminal) {
                $terminal->insertEdge($edge, $source);
            } elseif (null != $previous) {
                $previous->removeEdge($edge, $source);
            }
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        if ($this->maintainEdgeParent) {
            $this->updateEdgeParent($edge, $this->getRoot());
        }

        return $terminal;
    }

    /**
     * Function: setTerminals.
     *
     * Sets the source and target <mxCell> of the given <mxCell> in a single
     * transaction using <setTerminal> for each end of the edge.
     *
     * Parameters:
     *
     * edge - <mxCell> that specifies the edge.
     * source - <mxCell> that specifies the new source terminal.
     * target - <mxCell> that specifies the new target terminal.
     *
     * @param mixed $edge
     * @param mixed $source
     * @param mixed $target
     *
     * @throws Exception
     */
    public function setTerminals($edge, $source, $target): void
    {
        $this->beginUpdate();

        try {
            $this->setTerminal($edge, $source, true);
            $this->setTerminal($edge, $target, false);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();
    }

    /**
     * Function: getEdgeCount.
     *
     * Returns the number of distinct edges connected to the given cell.
     *
     * Parameters:
     *
     * cell - <mxCell> that represents the vertex.
     *
     * @param mxCell $cell
     *
     * @return int
     */
    public function getEdgeCount(mxCell $cell = null): int
    {
        return ($cell) ? $cell->getEdgeCount() : 0;
    }

    /**
     * Function: getEdgeAt.
     *
     * Returns the edge of cell at the given index.
     *
     * Parameters:
     *
     * cell - <mxCell> that specifies the vertex.
     * index - Integer that specifies the index of the edge
     * to return.
     *
     * @param mxCell $cell
     * @param int    $index
     *
     * @return null|mixed
     */
    public function getEdgeAt(mxCell $cell, int $index)
    {
        return $cell->getEdgeAt($index);
    }

    /**
     * Function: getEdges.
     *
     * Returns all distinct edges connected to this cell as an array of
     * <mxCells>. The return value should be only be read.
     *
     * Parameters:
     *
     * cell - <mxCell> that specifies the cell.
     *
     * @param mxCell $cell
     *
     * @return mxCell[]
     */
    public function getEdges(mxCell $cell): array
    {
        return $cell->edges;
    }

    /**
     * Function: isVertex.
     *
     * Returns true if the given cell is a vertex.
     *
     * Parameters:
     *
     * cell - <mxCell> that represents the possible vertex.
     *
     * @param mxCell $cell
     *
     * @return bool
     */
    public function isVertex(mxCell $cell): bool
    {
        return $cell->isVertex();
    }

    /**
     * Function: isEdge.
     *
     * Returns true if the given cell is an edge.
     *
     * Parameters:
     *
     * cell - <mxCell> that represents the possible edge.
     *
     * @param mxCell $cell
     *
     * @return bool
     */
    public function isEdge(mxCell $cell)
    {
        return $cell->isEdge();
    }

    /**
     * Function: isConnectable.
     *
     * Returns true if the given <mxCell> is connectable. If <edgesConnectable>
     * is false, then this function returns false for all edges else it returns
     * the return value of <mxCell.isConnectable>.
     *
     * Parameters:
     *
     * cell - <mxCell> whose connectable state should be returned.
     *
     * @param mxCell $cell
     *
     * @return bool
     */
    public function isConnectable(mxCell $cell): bool
    {
        return $cell->isConnectable();
    }

    /**
     * Function: getValue.
     *
     * Returns the user object of the given <mxCell> using <mxCell.getValue>.
     *
     * Parameters:
     *
     * cell - <mxCell> whose user object should be returned.
     *
     * @param mxCell $cell
     *
     * @return object
     */
    public function getValue(mxCell $cell)
    {
        return $cell->getValue();
    }

    /**
     * Function: setValue.
     *
     * Sets the user object of then given <mxCell> using <mxValueChange>
     * and adds the change to the current transaction.
     *
     * Parameters:
     *
     * cell - <mxCell> whose user object should be changed.
     * value - Object that defines the new user object.
     *
     * @param mixed $cell
     * @param mixed $value
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function setValue($cell, $value)
    {
        $this->beginUpdate();

        try {
            $cell->setValue($value);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        return $value;
    }

    /**
     * Function: getGeometry.
     *
     * Returns the <mxGeometry> of the given <mxCell>.
     *
     * Parameters:
     *
     * cell - <mxCell> whose geometry should be returned.
     *
     * @param mxCell $cell
     *
     * @return mxGeometry
     */
    public function getGeometry($cell): mxGeometry
    {
        return $cell->getGeometry();
    }

    /**
     * Function: setGeometry.
     *
     * Sets the <mxGeometry> of the given <mxCell>. The actual update
     * of the cell is carried out in <geometryForCellChanged>. The
     * <mxGeometryChange> action is used to encapsulate the change.
     *
     * Parameters:
     *
     * cell - <mxCell> whose geometry should be changed.
     * geometry - <mxGeometry> that defines the new geometry.
     *
     * @param mxCell     $cell
     * @param mxGeometry $geometry
     *
     * @throws Exception
     *
     * @return mxGeometry
     */
    public function setGeometry(mxCell $cell, mxGeometry $geometry): mxGeometry
    {
        $this->beginUpdate();

        try {
            $cell->setGeometry($geometry);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        return $geometry;
    }

    /**
     * Function: getStyle.
     *
     * Returns the style of the given <mxCell>.
     *
     * Parameters:
     *
     * cell - <mxCell> whose style should be returned.
     *
     * @param mxCell $cell
     *
     * @return string
     */
    public function getStyle(mxCell $cell): string
    {
        return$cell->getStyle();
    }

    /**
     * Function: setStyle.
     *
     * Sets the style of the given <mxCell> using <mxStyleChange> and
     * adds the change to the current transaction.
     *
     * Parameters:
     *
     * cell - <mxCell> whose style should be changed.
     * style - String of the form stylename[;key=value] to specify
     * the new cell style.
     *
     * @param mixed $cell
     * @param mixed $style
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function setStyle($cell, $style)
    {
        $this->beginUpdate();

        try {
            $cell->setStyle($style);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        return $style;
    }

    /**
     * Function: isCollapsed.
     *
     * Returns true if the given <mxCell> is collapsed.
     *
     * Parameters:
     *
     * cell - <mxCell> whose collapsed state should be returned.
     *
     * @param mixed $cell
     *
     * @return bool
     */
    public function isCollapsed($cell): bool
    {
        return (null != $cell) ? $cell->isCollapsed() : false;
    }

    /**
     * Function: setCollapsed.
     *
     * Sets the collapsed state of the given <mxCell> using <mxCollapseChange>
     * and adds the change to the current transaction.
     *
     * Parameters:
     *
     * cell - <mxCell> whose collapsed state should be changed.
     * collapsed - Boolean that specifies the new collpased state.
     *
     * @param mixed $cell
     * @param mixed $isCollapsed
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function setCollapsed($cell, $isCollapsed)
    {
        $this->beginUpdate();

        try {
            $cell->setCollapsed($isCollapsed);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        return $isCollapsed;
    }

    /**
     * Function: isVisible.
     *
     * Returns true if the given <mxCell> is visible.
     *
     * Parameters:
     *
     * cell - <mxCell> whose visible state should be returned.
     *
     * @param mixed $cell
     *
     * @return bool
     */
    public function isVisible($cell): bool
    {
        return (null != $cell) ? $cell->isVisible() : false;
    }

    /**
     * Function: setVisible.
     *
     * Sets the visible state of the given <mxCell> using <mxVisibleChange> and
     * adds the change to the current transaction.
     *
     * Parameters:
     *
     * cell - <mxCell> whose visible state should be changed.
     * visible - Boolean that specifies the new visible state.
     *
     * @param mixed $cell
     * @param mixed $visible
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function setVisible($cell, $visible)
    {
        $this->beginUpdate();

        try {
            $cell->setVisible($visible);
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();

        return $visible;
    }

    /**
     * Function: mergeChildren.
     *
     * Merges the children of the given cell into the given target cell inside
     * this model. All cells are cloned unless there is a corresponding cell in
     * the model with the same id, in which case the source cell is ignored and
     * all edges are connected to the corresponding cell in this model. Edges
     * are considered to have no identity and are always cloned unless the
     * cloneAllEdges flag is set to false, in which case edges with the same
     * id in the target model are reconnected to reflect the terminals of the
     * source edges.
     *
     * @param mixed $from
     * @param mixed $to
     * @param mixed $cloneAllEdges
     *
     * @throws Exception
     */
    public function mergeChildren($from, $to, $cloneAllEdges = true): void
    {
        $this->beginUpdate();

        try {
            $mapping = [];
            $this->mergeChildrenImpl($from, $to, $cloneAllEdges, $mapping);

            // Post-processes all edges in the mapping and
            // reconnects the terminals to the corresponding
            // cells in the target model
            foreach ($mapping as $key => $cell) {
                $terminal = $this->getTerminal($cell, true);

                if ($terminal) {
                    $terminal = $mapping[mxCellPath::create($terminal)];
                    $this->setTerminal($cell, $terminal, true);
                }

                $terminal = $this->getTerminal($cell, false);

                if ($terminal) {
                    $terminal = $mapping[mxCellPath::create($terminal)];
                    $this->setTerminal($cell, $terminal, false);
                }
            }
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();
    }

    /**
     * Function: mergeChildrenImpl.
     *
     * Clones the children of the source cell into the given target cell in
     * this model and adds an entry to the mapping that maps from the source
     * cell to the target cell with the same id or the clone of the source cell
     * that was inserted into this model.
     *
     * @param mxCell $from
     * @param mxCell $to
     * @param bool   $cloneAllEdges
     * @param mixed  $mapping
     *
     * @throws Exception
     */
    public function mergeChildrenImpl(mxCell $from, mxCell $to, bool $cloneAllEdges, &$mapping): void
    {
        $this->beginUpdate();

        try {
            $childCount = $from->getChildCount();

            for ($i = 0; $i < $childCount; ++$i) {
                $cell = $from->getChildAt($i);
                $id = $cell->getId();
                $target = (isset($id) && (!$this->isEdge($cell) || !$cloneAllEdges)) ?
                        $this->getCell($id) : null;

                // Clones and adds the child if no cell exists for the id
                if (!isset($target)) {
                    $clone = $cell->copy();
                    $clone->setId($id);

                    // Sets the terminals from the original cell to the clone
                    // because the lookup uses strings not cells in PHP
                    $clone->setTerminal($cell->getTerminal(true), true);
                    $clone->setTerminal($cell->getTerminal(false), false);

                    // Do *NOT* use model.add as this will move the edge away
                    // from the parent in updateEdgeParent if maintainEdgeParent
                    // is enabled in the target model
                    $target = $to->insert($clone);
                    $this->cellAdded($target);
                }

                // Stores the mapping for later reconnecting edges
                $mapping[mxCellPath::create($cell)] = $target;

                // Recurses
                $this->mergeChildrenImpl($cell, $target, $cloneAllEdges, $mapping);
            }
        } catch (Exception $e) {
            $this->endUpdate();

            throw($e);
        }
        $this->endUpdate();
    }

    /**
     * Function: beginUpdate.
     *
     * Increments the <updateLevel> by one. The event notification
     * is queued until <updateLevel> reaches 0 by use of
     * <endUpdate>.
     */
    public function beginUpdate(): void
    {
        ++$this->updateLevel;
    }

    /**
     * Function: endUpdate.
     *
     * Decrements the <updateLevel> by one and fires a notification event if
     * the <updateLevel> reaches 0. This function indirectly fires a
     * notification.
     */
    public function endUpdate(): void
    {
        --$this->updateLevel;

        if (0 === $this->updateLevel) {
            $this->fireEvent(new mxEventObject(mxEvent::$GRAPH_MODEL_CHANGED));
        }
    }

    /**
     * Function: getDirectedEdgeCount.
     *
     * Returns the number of incoming or outgoing edges, ignoring the given
     * edge.
     *
     * Parameters:
     *
     * cell - <mxCell> whose edge count should be returned.
     * outgoing - Boolean that specifies if the number of outgoing or
     * incoming edges should be returned.
     * ignoredEdge - <mxCell> that represents an edge to be ignored.
     *
     * @param mixed      $cell
     * @param mixed      $outgoing
     * @param null|mixed $ignoredEdge
     *
     * @return int
     */
    public function getDirectedEdgeCount($cell, $outgoing, $ignoredEdge = null): int
    {
        $count = 0;
        $edgeCount = $this->getEdgeCount($cell);

        for ($i = 0; $i < $edgeCount; ++$i) {
            $edge = $this->getEdgeAt($cell, $i);

            if ($edge !== $ignoredEdge &&
                $this->getTerminal($edge, $outgoing) === $cell) {
                ++$count;
            }
        }

        return $count;
    }
}
