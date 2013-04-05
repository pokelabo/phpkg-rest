<?php

namespace handler;

class UserHandler extends AppHandler {
    public function get() {
        if ($this->_request->getid()) {
            $this->setResponse(array('version' => 'Ver 1.0.0', 'id' => $this->_request->getId()));
            return;
        }
        $this->setResponse(array('version' => 'Ver 1.0.0'));
    }
    
    public function getVer1_1_1() {
        $this->setResponse(array('version' => 'Ver 1.1.1'));
    }
    
    public function getVer1_1_0() {
        $this->setResponse(array('version' => 'Ver 1.1.0'));
    }
}
