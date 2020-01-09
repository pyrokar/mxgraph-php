<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxEventSource
{
    /**
     * Class: mxEventSource.
     *
     * Base class for all event sources.
     *
     * Variable: eventListeners
     *
     * Holds the registered listeners.
     *
     * @var array<string, object>
     */
    protected $eventListeners = [];

    /**
     * Function: addListener.
     *
     * Adds a listener for the given event name. Note that the method of the
     * listener object must have the same name as the event it's being added
     * for. This is different from other language implementations of this
     * class.
     *
     * @param string $name
     * @param object $listener
     */
    public function addListener(string $name, $listener): void
    {
        $this->eventListeners[$name] = $listener;
    }

    /**
     * Function: fireEvent.
     *
     * Fires the event for the specified name.
     *
     * @param mxEventObject $event
     */
    public function fireEvent(mxEventObject $event): void
    {
        $name = $event->getName();

        foreach ($this->eventListeners as $_name => $listener) {
            if ($_name !== $name) {
                continue;
            }

            $listener->{$name}($event);
        }
    }
}
