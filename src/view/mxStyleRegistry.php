<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxStyleRegistry
{
    /**
     * Class: mxStyleRegistry.
     *
     * Singleton class that acts as a global converter from string to object values
     * in a style. This is currently only used to perimeters and edge styles.
     *
     * Variable: values
     *
     * Maps from strings to objects.
     */
    public static $values = [];

    /**
     * Function: putValue.
     *
     * Puts the given object into the registry under the given name.
     *
     * @param mixed $name
     * @param mixed $value
     */
    public static function putValue($name, $value): void
    {
        mxStyleRegistry::$values[$name] = $value;
    }

    /**
     * Function: getValue.
     *
     * Returns the value associated with the given name.
     *
     * @param mixed $name
     */
    public static function getValue($name)
    {
        return (isset(mxStyleRegistry::$values[$name])) ? mxStyleRegistry::$values[$name] : null;
    }

    /**
     * Function: getName.
     *
     * Returns the name for the given value.
     *
     * @param mixed $value
     */
    public static function getName($value)
    {
        foreach (mxStyleRegistry::$values as $key => $val) {
            if ($value === $val) {
                return $key;
            }
        }

        return null;
    }
}

mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_ELBOW, mxEdgeStyle::$ElbowConnector);
mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_ENTITY_RELATION, mxEdgeStyle::$EntityRelation);
mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_LOOP, mxEdgeStyle::$Loop);
mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_SIDETOSIDE, mxEdgeStyle::$SideToSide);
mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_TOPTOBOTTOM, mxEdgeStyle::$TopToBottom);

mxStyleRegistry::putValue(mxConstants::$PERIMETER_ELLIPSE, mxPerimeter::$EllipsePerimeter);
mxStyleRegistry::putValue(mxConstants::$PERIMETER_RECTANGLE, mxPerimeter::$RectanglePerimeter);
mxStyleRegistry::putValue(mxConstants::$PERIMETER_RHOMBUS, mxPerimeter::$RhombusPerimeter);
mxStyleRegistry::putValue(mxConstants::$PERIMETER_TRIANGLE, mxPerimeter::$TrianglePerimeter);
