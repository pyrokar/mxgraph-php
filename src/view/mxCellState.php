<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxCellState extends mxRectangle
{
    /**
     * Class: mxCellState.
     *
     * Represents the current state of a cell in a given <mxGraphView>.
     *
     * Variable: view
     *
     * Reference to the enclosing <mxGraphView>.
     *
     * @var mxGraphView
     */
    public $view;

    /**
     * Variable: cell.
     *
     * Reference to the <mxCell> that is represented by this state.
     *
     * @var mxCell
     */
    public $cell;

    /**
     * Variable: style.
     *
     * Contains an array of key, value pairs that represent the style of the
     * cell.
     *
     * @var array<string, string>
     */
    public $style;

    /**
     * Variable: invalid.
     *
     * Specifies if the state is invalid. Default is true.
     *
     * @var bool
     */
    public $invalid = true;

    /**
     * Variable: origin.
     *
     * <mxPoint> that holds the origin for all child cells. Default is a new
     * empty <mxPoint>.
     *
     * @var mxPoint
     */
    public $origin;

    /**
     * Variable: absolutePoints.
     *
     * Holds an array of <mxPoints> that represent the absolute points of an
     * edge.
     *
     * @var mxPoint[]
     */
    public $absolutePoints = [];

    /**
     * Variable: absoluteOffset.
     *
     * <mxPoint> that holds the absolute offset. For edges, this is the
     * absolute coordinates of the label position. For vertices, this is the
     * offset of the label relative to the top, left corner of the vertex.
     *
     * @var mxPoint
     */
    public $absoluteOffset;

    /**
     * Variable: terminalDistance.
     *
     * Caches the distance between the end points for an edge.
     *
     * @var float
     */
    public $terminalDistance;

    /**
     * Variable: length.
     *
     * Caches the length of an edge.
     *
     * @var float
     */
    public $length;

    /**
     * Variable: segments.
     *
     * Array of numbers that represent the cached length of each segment of the
     * edge.
     *
     * @var float[]
     */
    public $segments;

    /**
     * Variable: labelBounds.
     *
     * Holds the rectangle which contains the label.
     *
     * @var mxRectangle
     */
    public $labelBounds;

    /**
     * Variable: boundingBox.
     *
     * Holds the largest rectangle which contains all rendering for this cell.
     *
     * @var mxRectangle
     */
    public $boundingBox;

    /**
     * Constructor: mxCellState.
     *
     * Constructs a new object that represents the current state of the given
     * cell in the specified view.
     *
     * Parameters:
     *
     * view - <mxGraphView> that contains the state.
     * cell - <mxCell> that this state represents.
     * style - Array of key, value pairs that constitute the style.
     *
     * @param mxGraphView           $view
     * @param mxCell                $cell
     * @param array<string, string> $style
     */
    public function __construct(mxGraphView $view = null, mxCell $cell = null, array $style = null)
    {
        $this->view = $view;
        $this->cell = $cell;
        $this->style = $style;

        $this->origin = new mxPoint();
        $this->absoluteOffset = new mxPoint();
    }

    /**
     * Function: getPerimeterBounds.
     *
     * Returns the <mxRectangle> that should be used as the perimeter of the
     * cell.
     *
     * @param mixed $border
     *
     * @return mxRectangle
     */
    public function getPerimeterBounds($border = 0): mxRectangle
    {
        $bounds = new mxRectangle($this->x, $this->y, $this->width, $this->height);

        if (0 !== $border) {
            $bounds->grow($border);
        }

        return $bounds;
    }

    /**
     * Function: copy.
     *
     * Returns a copy of this state where all members are deeply cloned
     * except the view and cell references, which are copied with no
     * cloning to the new instance.
     *
     * @return self
     */
    public function copy(): self
    {
        $clone = new mxCellState($this->view, $this->cell, $this->style);

        // Clones the absolute points
        foreach ($this->absolutePoints as $absolutePoint) {
            $clone->absolutePoints[] = $absolutePoint->copy();
        }

        if (null != $this->origin) {
            $clone->origin = $this->origin->copy();
        }

        if (null != $this->absoluteOffset) {
            $clone->absoluteOffset = $this->absoluteOffset->copy();
        }

        if (null != $this->labelBounds) {
            $clone->labelBounds = $this->labelBounds->copy();
        }

        if (null != $this->boundingBox) {
            $clone->boundingBox = $this->boundingBox->copy();
        }

        $clone->terminalDistance = $this->terminalDistance;
        $clone->segments = $this->segments;
        $clone->length = $this->length;
        $clone->x = $this->x;
        $clone->y = $this->y;
        $clone->width = $this->width;
        $clone->height = $this->height;

        return $clone;
    }
}
