<?php

namespace handler;

class AppHandler {
    protected $_log;
    
    // リクエストとレスポンスを保持
    protected $_request;
    protected $_response;

    public function __construct($logger, $request, $response) {
        $this->_request = $request;
        $this->_response = $response;
        $this->_log = $logger;
    }

    protected function setResponse($result_map) {
        $this->_response->setContentTypeOutput($result_map + array('succeeded' => true));
    }
    
    protected function setErrorResponse($status_code, $error_map, $other_info = array()) {
        $this->_response->setStatusCode($status_code);
        $result_map = $other_info + array('errors' => $error_map, 'succeeded' => false);
        $this->_response->setJsonOutput($result_map);
    }
}
