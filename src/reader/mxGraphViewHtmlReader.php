<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxGraphViewHtmlReader extends mxGraphViewImageReader
{
    /**
     * @var mxHtmlCanvas
     */
    public $canvas;

    /**
     * Class: mxGraphViewHtmlReader.
     *
     * A display XML to HTML converter. This allows to create an image of a graph
     * without having to parse and create the graph model using the XML file
     * created for the mxGraphView object in the thin client.
     *
     * Constructor: mxGraphViewHtmlReader
     *
     * Constructs a new HTML graph view reader.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Function: createCanvas.
     *
     * Returns the canvas to be used for rendering.
     *
     * @param mixed $attrs
     *
     * @return mxGdCanvas
     */
    public function createCanvas($attrs): mxGdCanvas
    {
        return new mxHtmlCanvas($this->scale);
    }

    /**
     * Function: convert.
     *
     * Creates the HTML markup for the given display XML string.
     *
     * @param string $string
     * @param string $background
     *
     * @return string
     */
    public static function convert(string $string, string $background = null): string
    {
        $viewReader = new mxGraphViewHtmlReader();

        $viewReader->read($string);
        $html = $viewReader->canvas->getHtml();
        $viewReader->destroy();

        return $html;
    }

    /**
     * Function: convertFile.
     *
     * Creates the HTML markup for the given display XML file.
     *
     * @param string $filename
     * @param string $background
     *
     * @return string
     */
    public static function convertFile(string $filename, string $background = null): string
    {
        $viewReader = new mxGraphViewHtmlReader();

        $viewReader->readFile($filename);
        $html = $viewReader->canvas->getHtml();
        $viewReader->destroy();

        return $html;
    }
}
