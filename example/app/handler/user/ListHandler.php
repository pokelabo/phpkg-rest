<?php

namespace handler\user;


use handler\AppHandler;

class ListHandler extends AppHandler {
    public function get() {
        $this->setResponse(array('user list' => 'Ver 1.0.0'));
    }
    
    public function getVer1_1_1() {
        $this->setResponse(array('user list' => 'Ver 1.1.1'));
    }
    
    public function postVer2_2_1() {
        $this->setResponse(array('[POST]user list' => 'Ver 2.2.1'));
    }
    
    public function put() {
        $this->setResponse(array('1[PUT1]-user list' => 'Ver 1.0.0\ntest<br/>', 'array' => array('test1' => 'hoge', 'test2' => 'foo', 'test' => array('hhh', 'kkk'))));
    }
    
    public function putVer2_2_1() {
        $this->setResponse(array('[PUT]user list' => 'Ver 2.2.1'));
    }
}
