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
        libxml_use_internal_errors(true);
        $this->dom = new DOMDocument();
        $this->dom->loadHTML($html);
    }

    /**
     * @param $tagName
     * @return array
     */
    public function getPropertiesAndSlot($tagName)
    {
        if ($tagName instanceof \DOMNodeList) {
            $tags = $tagName;
        } else {
            $tags = $this->dom->getElementsByTagName($tagName);
        }

        $result = [];
        foreach ($tags as $tag) {
            $properties = [];
            foreach ($tag->attributes as $attr) {
                $properties[$attr->nodeName] = $attr->textContent;
            }

            $result[] = [
                'properties' => $properties,
                'slot' => $tag->textContent,
                'tagElement' => $tag
            ];
        }

        return $result;
    }

    public function getSubTags(array $tags)
    {
        $result = [];
        foreach ($tags as $tag_parent => $sub_tag) {
            $result[$tag_parent] = $this->getPropertiesAndSlot($tag_parent);
            foreach ($result[$tag_parent] as $key => $tag) {
                foreach ($tag['tagElement']->childNodes as $sub_tag_element) {
                    $extracted = $this->extractChildNodes($sub_tag_element);
                    if ($extracted) {
                        $result[$tag_parent][$key]['sub_tags'][] = $extracted;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $child_node
     * @return array
     */
    private function extractChildNodes($child_node)
    {
        $properties = [];

        if (get_class($child_node) !== 'DOMElement') {
            return;
        }

        $length = $child_node->attributes->length;
        for ($i = 0; $i < $length; ++$i) {
            $name = $child_node->attributes->item($i)->name;
            $value = $child_node->attributes->item($i)->value;
            $properties[$child_node->nodeName][$name] = $value;

        }

        return $properties;
    }
}