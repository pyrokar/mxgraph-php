<?php

declare(strict_types=1);

namespace MxGraph;

use Safe\Exceptions\StringsException;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxCellPath
{
    /**
     * Class: mxCellPath.
     *
     * Implements a mechanism for temporary cell Ids.
     *
     * Variable: codecs
     *
     * Maps from constructor names to codecs.
     *
     * @var string
     */
    public static $PATH_SEPARATOR = '.';

    /**
     * Function: create.
     *
     * Creates the cell path for the given cell. The cell path is a
     * concatenation of the indices of all ancestors on the (finite) path to
     * the root, eg. "0.0.0.1".
     *
     * Parameters:
     *
     * cell - Cell whose path should be returned.
     *
     * @param mxCell $cell
     *
     * @throws StringsException
     *
     * @return string
     */
    public static function create(mxCell $cell): string
    {
        $result = '';
        $parent = $cell->getParent();

        while ($parent) {
            $index = $parent->getIndex($cell);
            $result = $index.self::$PATH_SEPARATOR.$result;

            $cell = $parent;
            $parent = $cell->getParent();
        }

        return (strlen($result) > 1) ? \Safe\substr($result, 0, -1) : '';
    }

    /**
     * Function: getParentPath.
     *
     * Returns the cell for the specified cell path using the given root as the
     * root of the path.
     *
     * Parameters:
     *
     * path - Path whose parent path should be returned.
     *
     * @param string $path
     *
     * @throws StringsException
     *
     * @return string
     */
    public static function getParentPath(string $path = '')
    {
        if ('' !== $path) {
            $index = strrpos($path, self::$PATH_SEPARATOR);

            if (false === $index) {
                return '';
            }

            return \Safe\substr($path, 0, $index);
        }

        return '';
    }

    /**
     * Function: resolve.
     *
     * Returns the cell for the specified cell path using the given root as the
     * root of the path.
     *
     * Parameters:
     *
     * root - Root cell of the path to be resolved.
     * path - String that defines the path.
     *
     * @param mxCell $root
     * @param string $path
     *
     * @return mxCell
     */
    public static function resolve(mxCell $root, string $path): mxCell
    {
        $parent = $root;
        $tokens = explode(self::$PATH_SEPARATOR, $path);

        if (false === $tokens) {
            return $parent;
        }

        foreach ($tokens as $i => $iValue) {
            $parent = $parent->getChildAt($tokens[$i]);
        }

        return $parent;
    }
}
