<?php

namespace Yabafinet\WayEnd\CompileEngine;

use DOMDocument;

class CustomTagParser
{
    /**
     * @var DOMDocument
     */
    private $dom;

    public function __construct($html)
    {
        $this->dom = new DOMDocument();
        $this->dom->loadHTML($html);
    }

    /**
     * @param $tagName
     * @return array
     */
    public function getPropertiesAndSlot($tagName)
    {
        $tags = $this->dom->getElementsByTagName($tagName);
        $result = [];

        foreach ($tags as $tag) {
            $properties = [];
            foreach ($tag->attributes as $attr) {
                $properties[$attr->nodeName] = $attr->nodeValue;
            }

            $result[] = [
                'properties' => $properties,
                'slot' => $tag->nodeValue
            ];
        }

        return $result;
    }
}