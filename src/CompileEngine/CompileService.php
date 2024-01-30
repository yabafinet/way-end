<?php

namespace Yabafinet\WayEnd\CompileEngine;

use Yabafinet\WayEnd\Vue\CompileVueInstance;
use Yabafinet\WayEnd\WayEndService;

class CompileService
{
    /**
     * @var WayEndService
     */
    private $wayEndService;
    /**
     * @var string
     */
    private $patch;
    /**
     * @var string
     */
    public $file_source;
    /**
     * @var false|string
     */
    public $compile_component;
    /**
     * @var mixed
     */
    public $component_class;

    public function __construct(WayEndService $wayEndService)
    {
        $this->wayEndService = $wayEndService;
    }

    /**
     * @return CompileService
     */
    public function compile()
    {
        $file_compile = $this->createFileCompiled();
        ob_start();

        // include component file compiled
        include $file_compile;

        // create instance of component
        $this->wayEndService->component_class = new $this->wayEndService->component_name();

        // compile vue-js instance
        (new CompileVueInstance())->template($this->wayEndService);

        $this->compile_component = ob_get_clean();

        return $this;
    }

    /**
     * @return string
     */
    private function createFileCompiled()
    {
        $file_component = $this->wayEndService->component_file;
        $originalFile = fopen($file_component, 'r');
        $content = fread($originalFile, filesize($file_component));

        // GET CUSTOM TAGS [NOTE: move to other class]
        $parser = new CustomTagParser($content);
        $result = $parser->getPropertiesAndSlot('wn-suspense');
        // END GET CUSTOM TAGS

        // COMPILE DIRECTIVES [NOTE: move to other class]
        $content = str_replace('<wn-tpl>', '<?php function _wn_template() {?>', $content);
        $content = str_replace('</wn-tpl>', '<?php }?>', $content);
        // END COMPILE DIRECTIVES

        $this->file_source = $this->fileSourceFinal($file_component);
        $file_compiled = fopen($this->file_source, 'w');
        fwrite($file_compiled, $content);

        // close both files
        fclose($originalFile);
        fclose($file_compiled);
        return $this->file_source;
    }

    /**
     * @param $file
     * @return string
     */
    private function fileSourceFinal($file)
    {
        return $this->patch . md5($file) . '.php';
    }

    /**
     * @param $patch
     * @return void
     */
    public function patch($patch)
    {
        $this->patch = $patch;
    }
}