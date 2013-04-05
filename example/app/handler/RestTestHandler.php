<?php

namespace handler;

class RestTestHandler extends AppHandler {
    public function get() {
        $this->getCommon('Ver 1.0.0');
    }
    
    public function getVer1_1_0() {
        $this->getCommon('Ver 1.1.0');
    }
    
    public function getVer1_1_1() {
        $this->getCommon('Ver 1.1.1');
    }
    
    private function getCommon($version) {
        // クエリーを取得した場合
        $query_map = $this->_request->getQueryParam();
        if (isset($query_map['test'])) {
            $this->setResponse(array('version' => $version, 'test' => $query_map['test']));
        } else if ($this->_request->getid()) {
            $this->setResponse(array('version' => $version, 'id' => $this->_request->getId()));
        } else {
            $this->setResponse(array('version' => $version));
        }
    }
    
    public function post() {
        $input_map = $this->_request->getInput();
        if (isset($input_map['test'])) {
            $this->setResponse(array('post' => 'true', 'post_test' => $input_map['test']));
        } else {
            $this->setResponse(array('post' => 'true'));
        }
    }
    
    public function postVer2_2_3() {
        $this->setResponse(array('post' => 'true'));
    }
    
    public function put() {
        $this->setResponse(array('put' => 'true'));
    }
    
    public function delete() {
        $this->setResponse(array('delete' => 'true'));
    }
    
}
