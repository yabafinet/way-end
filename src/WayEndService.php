<?php
namespace Yabafinet\WayEnd;

use Yabafinet\WayEnd\VueComponent\CompileVueInstance;

class WayEndService
{
    /**
     * @var ReflectionClass
     */
    private $reflectionClass;
    /**
     * @var mixed
     */
    private $component_name;

    /**
     * @var
     */
    private $component_class;
    /**
     * @var mixed|null
     */
    private $route_patch;
    /**
     * @var mixed|null
     */
    private $component_patch;
    /**
     * @var mixed
     */
    private $request;
    /**
     * @var mixed|null
     */
    private $call_action;
    /**
     * @var array|null
     */
    private $call_method;
    /**
     * @var array|mixed
     */
    private $call_method_args;


    /**
     * @throws \ReflectionException
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function catchActions()
    {
        $this->bootRequired();
        $this->reflectionClass();
        $this->component_class = new $this->component_name();
        $this->mounted();
        $this->updatePropertiesFromRequest();

        if (isset($this->call_action) && $this->call_action == 'update') {
            $datas = $this->buildPropertiesJs();

            if (isset($this->call_method)) {
//                $methodCall = $this->component_class->{$this->call_method}();
                $methodCall = call_user_func_array(array($this->component_class, $this->call_method), $this->call_method_args);

                foreach ($datas as $property) {
                    $new_value = $this->component_class->{$property['name']};
                    $property['value'] = $new_value;
                    $datas[] = $property;
                }
            }

            exit(json_encode(['data' => $datas]));
        }
    }

    /**
     * @return array
     */
    public function buildPropertiesJs()
    {
        $properties = $this->getProperties();
        $datas = array();
        foreach ($properties as $property) {
            $default_value = $this->component_class->{$property->getName()} ?: 0;
            $datas[] = ['name' => $property->getName(), 'value' => $default_value];
        }
        return $datas;
    }

    /**
     * @return false|string
     */
    public function propertiesJsObject($datas = array())
    {
        $properties = $this->getProperties();
        foreach ($properties as $property) {
            $default_value = $this->component_class->{$property->getName()} ?: '';
            $datas[$property->getName()] = $default_value;
        }

        return json_encode($datas);
    }

    /**
     * @return string
     */
    public function buildMethodsInJs()
    {
        $methods = $this->reflectionClass->getMethods();
        $methods_in_js = '';
        foreach ($methods as $method) {
            //print_r([$method->getName(), $method->getParameters()]);
            // build parameters
            $parameters = [];
            foreach ($method->getParameters() as $parameter) {
                $parameters[] = $parameter->getName();
            }
            $methods_in_js .= $method->getName() . ': function ('.implode(',', $parameters).') { this.sendUpdate(this, "'.$method->getName().'", arguments); },';
        }
        return $methods_in_js;
    }

    /**
     * @param $params
     * @return string
     */
    public function getCurrentUrl($params = null)
    {
        list($component, $action) = $this->buildRequestQueryString();
        $action = isset($params['act']) ? $params['act'] : $action;
        $current = $this->route_patch . '?' . $component . '::' . $action;
        return $current;
    }

    /**
     * @param $route
     * @return void
     */
    public function route($route)
    {
        $this->route_patch  = $route;
    }

    /**
     * @param mixed|null $component_patch
     */
    public function loadComponents($component_patch)
    {
        $this->component_patch = $component_patch;
    }

    /**
     * @return void
     */
    private function bootRequired()
    {
        list($component, $action) = $this->buildRequestQueryString();
        $this->call_action = $action;
        $class_component = $this->component_patch . $component;
        $class_name = basename($class_component);
        $this->component_name = $class_name;
        require_once $class_component.'.php';
    }

    /**
     * @return array
     */
    private function buildRequestQueryString()
    {
        $queryString = $_SERVER['QUERY_STRING'];
        $queryString = explode('::', $queryString);
        $component = str_replace('.', '/' , $queryString[0]);
        $action = isset($queryString[1]) ? $queryString[1] : null;
        return [$component, $action];
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function reflectionClass()
    {
        $this->reflectionClass = new \ReflectionClass($this->component_name);
    }

    /**
     * @return void|mixed
     */
    public function template()
    {
        return $this->component_class->template();
    }

    /**
     * @return ReflectionProperty[]
     */
    private function getProperties()
    {
        return $this->reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * @return void
     */
    private function mounted()
    {
        if (!isset($this->request['method'])) {
            $this->component_class->mount();
        }
    }

    /**
     * @return void
     */
    public function updatePropertiesFromRequest()
    {
        $input = file_get_contents('php://input');
        $property_from_request = json_decode($input, true);
        if (is_array($property_from_request)) {
            // defining action and method
            $this->call_method = $property_from_request['method'] ?: null;
            $this->call_method_args = $property_from_request['args'] ?: [];

            // change properties values from request
            foreach ($property_from_request['changed'] as $key => $value) {
                $this->component_class->{$key} = $value;
            }
        }
    }

    public function compileJs()
    {
        (new CompileVueInstance())->template($this);
    }
}