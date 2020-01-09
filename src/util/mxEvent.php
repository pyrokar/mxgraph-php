<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxEvent
{
    /**
     * Class: mxEvent.
     *
     * Defines global constants.
     *
     * Variable: GRAPH_MODEL_CHANGED
     *
     * Defines the name of the graphModelChanged event.
     *
     * @var string
     */
    public static $GRAPH_MODEL_CHANGED = 'graphModelChanged';

    /**
     * Variable: SCALE.
     *
     * Defines the name of the scale event.
     *
     * @var string
     */
    public static $SCALE = 'scale';

    /**
     * Variable: TRANSLATE.
     *
     * Defines the name of the translate event.
     *
     * @var string
     */
    public static $TRANSLATE = 'translate';
}
