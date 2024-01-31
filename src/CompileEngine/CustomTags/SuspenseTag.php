<?php

namespace Yabafinet\WayEnd\CompileEngine\CustomTags;

use Yabafinet\WayEnd\CompileEngine\CustomTagParser;

class SuspenseTag
{
    /**
     * @var CustomTagParser

     */
    private $tagParser;
    /**
     * @var array
     */
    private $tags;

    public function __construct($tagParser)
    {
        $this->tagParser = $tagParser;
    }

    public function getTags()
    {
        $this->tags = $this->tagParser->getTag('wn-suspense');
    }

    public function buildRequestId()
    {
        // add requestId=1234 to properties contain click event
        foreach ($this->tags as $tag) {
            $tag['properties']['wn-suspense']['attr']['requestId'] = '1234';
        }
    }
}