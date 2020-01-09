<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxEventObject
{
    /**
     * Class: mxEventObject.
     *
     * Base class for all events.
     *
     * Variable: name
     *
     * Holds the name of the event.
     *
     * @var string
     */
    public $name;

    /**
     * Variable: properties.
     *
     * Holds the event properties in an associative array that maps from string
     * (key) to object (value).
     *
     * @var array<string, object>
     */
    public $properties;

    /**
     * Variable: consumed.
     *
     * Holds the consumed state of the event. Default is false.
     *
     * @var bool
     */
    public $consumed = false;

    /**
     * Constructor: mxEventObject.
     *
     * Constructs a new event for the given name and properties. The optional
     * properties are specified using a sequence of keys and values, eg.
     * new mxEventObject($name, $key1, $value1, $key2, $value2, .., $keyN, $valueN)
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->properties = [];
        $args = func_get_args();

        for ($i = 1, $iMax = count($args); $i < $iMax; $i += 2) {
            if (isset($args[$i + 1])) {
                $this->properties[$args[$i]] = $args[$i + 1];
            }
        }
    }

    /**
     * Function: getName.
     *
     * Returns <name>.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Function: getProperties.
     *
     * Returns <properties>.
     *
     * @return array<string, object>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Function: getProperty.
     *
     * Returns the property value for the given key.
     *
     * @param string $key
     *
     * @return object
     */
    public function getProperty(string $key): object
    {
        return $this->properties[$key];
    }

    /**
     * Function: isConsumed.
     *
     * Returns true if the event has been consumed.
     *
     * @return bool
     */
    public function isConsumed(): bool
    {
        return $this->consumed;
    }

    /**
     * Function: consume.
     *
     * Consumes the event.
     */
    public function consume(): void
    {
        $this->consumed = true;
    }
}
