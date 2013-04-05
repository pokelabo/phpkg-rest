<?php

namespace handler;

class TestHandler extends AppHandler {
    public function get() {
        $this->setResponse(array('app' => 'test'));
    }
    
    public function getVer1_1_1() {
        $this->setResponse(array('app' => 'Ver 1.1.1'));
    }
}
