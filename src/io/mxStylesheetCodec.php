<?php

declare(strict_types=1);

namespace MxGraph;

use DOMElement;
use Safe\Exceptions\StringsException;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxStylesheetCodec extends mxObjectCodec
{
    /**
     * Class: mxStylesheetCodec.
     *
     * Codec for <mxStylesheets>. This class is created and registered
     * dynamically at load time and used implicitely via <mxCodec>
     * and the <mxCodecRegistry>.
     *
     * Constructor: mxObjectCodec
     *
     * Constructs a new codec for the specified template object.
     * The variables in the optional exclude array are ignored by
     * the codec. Variables in the optional idrefs array are
     * turned into references in the XML. The optional mapping
     * may be used to map from variable names to XML attributes.
     *
     * Parameters:
     *
     * template - Prototypical instance of the object to be
     * encoded/decoded.
     * exclude - Optional array of fieldnames to be ignored.
     * idrefs - Optional array of fieldnames to be converted to/from
     * references.
     * mapping - Optional mapping from field- to attributenames.
     *
     * @param mixed $template
     */
    public function __construct($template)
    {
        parent::__construct($template);
    }

    /**
     * Override <mxObjectCodec.encode>.
     *
     * @param mxCodec      $enc
     * @param mxStylesheet $obj
     *
     * @return DOMElement
     */
    public function encode(mxCodec $enc, $obj): DOMElement
    {
        $node = $enc->document->createElement($this->getName());

        foreach ($obj->styles as $i => $style) {
            $styleNode = $enc->document->createElement('add');

            $styleNode->setAttribute('as', $i);

            foreach ($style as $j => $value) {
                $value = $this->getStringValue($j, $value);

                if (isset($value)) {
                    $entry = $enc->document->createElement('add');
                    $entry->setAttribute('value', $value);
                    $entry->setAttribute('as', $j);
                    $styleNode->appendChild($entry);
                }
            }

            if ($styleNode->childNodes->count() > 0) {
                $node->appendChild($styleNode);
            }
        }

        return $node;
    }

    /**
     * Returns the string for encoding the given value.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return null|mixed
     */
    public function getStringValue($key, $value)
    {
        return (!function_exists($value) && !is_object($value)) ? $value : null;
    }

    /**
     * Override <mxObjectCodec.decode>.
     *
     * @param mxCodec    $dec
     * @param DOMElement $node
     * @param null|mixed $into
     *
     * @throws StringsException
     *
     * @return null|mixed
     */
    public function decode(mxCodec $dec, DOMElement $node, &$into = null)
    {
        $id = $node->getAttribute('id');
        $obj = $dec->objects[$id] ?? null;

        if (!$obj) {
            if ($into) {
                $obj = $into;
            } elseif (!is_array($this->template)) {
                $tmp = get_class($this->template);
                $obj = new $tmp();
            }

            if ('' !== $id) {
                $dec->putObject($id, $obj);
            }
        }

        $node = $node->firstChild;

        while ($node instanceof DOMElement) {
            if (!$this->processInclude($dec, $node, $obj) && 'add' === $node->nodeName) {
                $as = $node->getAttribute('as');

                if (strlen($as) > 0) {
                    $extend = $node->getAttribute('extend');

                    $style = (strlen($extend) > 0 &&
                        isset($obj->styles[$extend])) ?
                        array_slice($obj->styles[$extend], 0) :
                        null;

                    if (!isset($style)) {
                        $style = [];
                    }

                    $entry = $node->firstChild;

                    while ($entry instanceof DOMElement) {
                        $key = $entry->getAttribute('as');

                        if ('add' === $entry->nodeName) {
                            $text = $entry->textContent;
                            $value = null;

                            if ('' !== $text) {
                                $value = mxUtils::evaluate($text);
                            } else {
                                $value = $entry->getAttribute('value');
                            }

                            if ($value) {
                                $style[$key] = $value;
                            }
                        } elseif ('remove' === $entry->nodeName) {
                            unset($style[$key]);
                        }

                        $entry = $entry->nextSibling;
                    }

                    $obj->putCellStyle($as, $style);
                }
            }

            $node = $node->nextSibling;
        }

        return $obj;
    }
}

mxCodecRegistry::register(new mxStylesheetCodec(new mxStylesheet()));
