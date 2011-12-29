<?php

namespace PhpRestService\Resource\Display;
use \PhpRestService\Resource\Component;
use \PhpRestService\Logger;

abstract class DisplayAbstract extends Component\ComponentAbstract {

    public function dataUrl($object) {
        $data = array();
        if (method_exists($object, 'getId')) {
            $id = $object->getId();
            if (isset($id)) {
                $data = array (
                    'id' => $object->getId(),
                    'url' => 'http://' . $_SERVER['SERVER_NAME'] . '/blog/post/' . $object->getId(),
                );
            }
        }
        return $data;
    }

    public function displayItem($object, $extended = false) {
        // Basic data
        $data = $this->dataBasic($object);

        if ($extended) {
            $data = array_merge($data, $this->dataExtended($object));
        } else {
            $data = array_merge($data, $this->dataUrl($object));
        }

        return $data;
    }

    public function displayCollection($objects, $extended = false) {
        $data = array();
        if (is_array($objects)) {
            foreach($objects as $object) {
                $data[] = $this->displayItem($object, $extended);
            }
        } else {
            Logger::log("Error display items", \Zend_Log::DEBUG);
        }
        return $data;
    }

    public function handle($inputData = array(), $extended = NULL) {
        if ($this->getId()) {
            $extended = (!is_null($extended)) ? $extended : TRUE;
            $display = $this->displayItem($inputData, $extended);
        } else {
            $extended = (!is_null($extended)) ? $extended : FALSE;
            $display = $this->displayCollection($inputData, $extended);
        }
        return $display;
    }

}
