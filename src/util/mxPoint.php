<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxPoint
{
    /**
     * Class: mxPoint.
     *
     * Implements a 2-dimensional point with double precision coordinates.
     *
     * Variable: x
     *
     * Holds the x-coordinate of the point. Default is 0.
     *
     * @var float
     */
    public $x = 0;

    /**
     * Variable: y.
     *
     * Holds the y-coordinate of the point. Default is 0.
     *
     * @var float
     */
    public $y = 0;

    /**
     * Constructor: mxPoint.
     *
     * Constructs a new point for the optional x and y coordinates. If no
     * coordinates are given, then the default values for <x> and <y> are used.
     *
     * @param mixed $x
     * @param mixed $y
     */
    public function __construct($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Function: equals.
     *
     * Returns true if the given object equals this point.
     *
     * @param mixed $obj
     *
     * @return bool
     */
    public function equals($obj): bool
    {
        if ($obj instanceof self) {
            return $obj->x === $this->x && $obj->y === $this->y;
        }

        return false;
    }

    /**
     * Function: copy.
     *
     * Returns a copy of this <mxPoint>.
     *
     * @return mxPoint
     */
    public function copy()
    {
        return new mxPoint($this->x, $this->y);
    }
}
