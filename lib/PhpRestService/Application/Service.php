<?php

namespace PhpRestService\Application;

use PhpRestService\Http;
use PhpRestService\Resource;
use PhpRestService\Resource\Format;

class Service extends ApplicationAbstract implements ApplicationInterface {

    protected $_request;
    protected $_response;


    protected function _loadResource($router) {
        $resourceName = $router->getResource();

        $resourceManager = new \PhpRestService\Resource\Manager\ManagerDefault();
        $baseClass = '\\App\\Service\\' . $resourceName;

        // Set formatter
        if (class_exists($baseClass . '\\Display')) {
            $formatterClass = $baseClass . '\\Display';
            \PhpRestService\Logger::get()->log('Setting custom formatter: ' . $formatterClass, \Zend_Log::INFO);
        } else {
            $formatterClass = '\\PhpRestService\\Resource\\Display\\All';
            \PhpRestService\Logger::get()->log('Setting default formatter: ' . $formatterClass, \Zend_Log::INFO);
        }
        $formatter = new $formatterClass();
        $resourceManager->setDisplay($formatter);

        $matches = array();
        if (preg_match('#/([0-9]+)$#', ($_SERVER['REQUEST_URI']), $matches)) {
            // Item resource
            \PhpRestService\Logger::get()->log('Trying item resource: GET: ' . $resourceName, \Zend_Log::INFO);
            if (class_exists($baseClass . '\\Item')) {
                $resourceManager->setId($matches[0]);
                \PhpRestService\Logger::get()->log('Loading item resource: GET: ' . $resourceName, \Zend_Log::INFO);

                $itemClass = $baseClass . '\\Item';
                $item = new $itemClass();
                $resourceManager->setItem($item);
            }
        } else {
            \PhpRestService\Logger::get()->log('Trying collection resource: GET: ' . $baseClass . '\\Collection', \Zend_Log::INFO);
            // Collection resource
            if (class_exists($baseClass . '\\Collection')) {
                \PhpRestService\Logger::get()->log('Loading collection resource: GET: ' . $resourceName, \Zend_Log::INFO);

                $collectionClass = $baseClass . '\\Collection';
                $collection = new $collectionClass();
                $resourceManager->setCollection($collection);
            }
        }

        return $resourceManager;
    }


    protected function _detectRoute() {
        \PhpRestService\Logger::get()->log('Detecting route: ' . $_SERVER['REQUEST_METHOD'] . ': ' . $_SERVER['REQUEST_URI'], \Zend_Log::INFO);
        $router = new \PhpRestService\Application\Router\Resource($_SERVER['REQUEST_URI']);

        return $router;
    }


    protected function _renderOutput($resource) {
        \PhpRestService\Logger::get()->log('Rendering resource: GET: ' . get_class($resource), \Zend_Log::INFO);

        // Format
        $format = (isset($_REQUEST['format'])) ? $_REQUEST['format'] : 'json';
        $response = new \PhpRestService\Http\Response();
        switch ($format) {
            case 'xml':
                $representation = new Format\Xml($response);
                break;
            case 'json':
            default:
                $representation = new Format\Json($response);
                break;
        }

        $data = $resource->handle();
        $this->_response = $representation->render($data);
    }


    public function run() {
        $this->_init();

        // Detect Route
        $route = $this->_detectRoute();

        // Load resource
        $resource = $this->_loadResource($route);

        // Init representation
        $this->_renderOutput($resource);

        return $this->_response->send();
    }

}
