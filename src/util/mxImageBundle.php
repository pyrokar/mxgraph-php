<?php

declare(strict_types=1);

namespace MxGraph;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxImageBundle
{
    /**
     * Class: mxImageBundle.
     *
     * Maps from keys to base64 encoded images or file locations. All values must
     * be URLs or use the format data:image/format followed by a comma and the base64
     * encoded image data, eg. "data:image/gif,XYZ", where XYZ is the base64 encoded
     * image data.
     *
     * (code)
     * $bundle = new mxImageBundle();
     * $bundle->putImage("myImage", "data:image/gif,R0lGODlhEAAQAMIGAAAAAICAAICAgP".
     *   "//AOzp2O3r2////////yH+FUNyZWF0ZWQgd2l0aCBUaGUgR0lNUAAh+QQBCgAHACwAAAAAEA".
     *   "AQAAADTXi63AowynnAMDfjPUDlnAAJhmeBFxAEloliKltWmiYCQvfVr6lBPB1ggxN1hilaSS".
     *   "ASFQpIV5HJBDyHpqK2ejVRm2AAgZCdmCGO9CIBADs=");
     * $graph->addImageBundle($bundle);
     * (end);
     *
     * The image can then be referenced in any cell style using image=myImage.
     *
     * To convert an image at a given URL to a base64 encoded String, the following
     * code can be used:
     *
     * (code)
     * echo "base64=".base64_encode(file_get_contents($url));
     * (end)
     *
     * The value is decoded in <mxUtils.loadImage>. The keys for images are
     * resolved and the short format above is converted to a data URI in
     * <mxGraph.postProcessCellStyle>.
     *
     * Variable: images
     *
     * Maps from keys to images.
     *
     * @var array<string, string>
     */
    public $images = [];

    /**
     * Constructor: mxImageBundle.
     *
     * Constructs a new image bundle.
     */
    public function __construct()
    {
    }

    /**
     * Function: getImages.
     *
     * Returns the <images>.
     *
     * @return array<string, string>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Function: putImage.
     *
     * Adds the specified entry to the map.
     *
     * @param string $key
     * @param string $value
     */
    public function putImage(string $key, string $value): void
    {
        $this->images[$key] = $value;
    }

    /**
     * Function: getImage.
     *
     * Returns the value for the given key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getImage($key): ?string
    {
        return $this->images[$key] ?? null;
    }
}
