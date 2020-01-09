<?php

declare(strict_types=1);

namespace MxGraph;

use Exception;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxCodecRegistry
{
    /**
     * Class: mxCodecRegistry.
     *
     * A class to register codecs for objects.
     *
     * Variable: codecs
     *
     * Maps from constructor names to codecs.
     *
     * @var array<string, mxObjectCodec>
     */
    public static $codecs = [];

    /**
     * Variable: aliases.
     *
     * Maps from classnames to codecnames.
     *
     * @var array<string, string>
     */
    public static $aliases = [];

    /**
     * Function: register.
     *
     * Registers a new codec and associates the name of the template constructor
     * in the codec with the codec object. Automatically creates an alias if the
     * codename and the classname are not equal.
     *
     * Parameters:
     *
     * codec - <mxObjectCodec> to be registered.
     *
     * @param mxObjectCodec $codec
     *
     * @return mixed
     */
    public static function register(mxObjectCodec $codec)
    {
        $name = $codec->getName();
        self::$codecs[$name] = $codec;

        $classname = self::getName($codec->template);

        if ($classname !== $name) {
            self::addAlias($classname, $name);
        }

        return $codec;
    }

    /**
     * Function: addAlias.
     *
     * Adds an alias for mapping a classname to a codecname.
     *
     * @param mixed $classname
     * @param mixed $codecname
     */
    public static function addAlias($classname, $codecname): void
    {
        self::$aliases[$classname] = $codecname;
    }

    /**
     * Function: getCodec.
     *
     * Returns a codec that handles objects that are constructed
     * using the given ctor.
     *
     * Parameters:
     *
     * ctor - JavaScript constructor function.
     *
     * @param mixed $name
     *
     * @return null|mixed|mxObjectCodec
     */
    public static function getCodec($name)
    {
        $codec = null;

        if (isset($name)) {
            if (isset(self::$aliases[$name])) {
                $tmp = self::$aliases[$name];

                if ('' !== $tmp) {
                    $name = $tmp;
                }
            }

            $codec = self::$codecs[$name] ?? null;

            // Registers a new default codec for the given constructor
            // if no codec has been previously defined.
            if (!isset($codec)) {
                try {
                    $obj = self::getInstanceForName($name);

                    if ($obj) {
                        $codec = new mxObjectCodec($obj);
                        self::register($codec);
                    }
                } catch (Exception $e) {
                    // ignore
                }
            }
        }

        return $codec;
    }

    /**
     * Function: getInstanceForName.
     *
     * Creates and returns a new instance for the given class name.
     *
     * @param mixed $name
     *
     * @return object | null
     */
    public static function getInstanceForName($name): ?object
    {
        if (class_exists($name)) {
            return new $name();
        }

        return null;
    }

    /**
     * Function: getName.
     *
     * Returns the codec name for the given object instance.
     *
     * Parameters:
     *
     * obj - PHP object to return the codec name for.
     *
     * @param mixed $obj
     *
     * @return string
     */
    public static function getName($obj): string
    {
        if (is_array($obj)) {
            return 'Array';
        }

        return get_class($obj);
    }
}
