<?php

namespace pokelabo\framework;

use pokelabo\http\Request;
use pokelabo\http\Response;
use pokelabo\http\ResponseRenderer;
use pokelabo\utility\StringUtility;

use pokelabo\config\ConfigRepository;

class RestfulApp {
    protected $_log;
    // クエリーストリングを保持します。
    private $_get_string;
    // バージョン情報を保持します。
    private $_version;

    public function __construct($logger = null) {
        if ($logger) {
            $this->_log = $logger;
        } else {
            $this->_log = new LoggerDummy();
        }
    }
    
    public function run() {
        // リクエストとレスポンズを生成します。
        $request = $this->createRequest();
        $response = $this->createResponse();
        
        // HTTPからのリクエストを流します。
        $this->dispatch($request, $response);
        
        $renderer = $this->createResponseRenderer();
        $renderer->render($response);
    }

    // テストコードからも実行される事があるので、runメソッドの中身を別にする。
    public function dispatch($request, $response) {
        // URLパース用にクエリーストリングを保持します。
        $this->_get_string = $_GET['__route__'];
        // 元のクエリーストリングは削除する。
        unset($_GET['__route__']);
        
        // IDを設定します。
        $this->setRequestId($request);
        
        // バージョン番号を取得します。
        $this->_version = $this->findVersion();
        
        // バージョン番号が無い場合は不正とする。
        if (!isset($this->_version)) {
            return $this->endWithHttpError(404);
        }
        
        // 使用するハンドラーを取得します。
        $handler = $this->findHandler($request, $response);
        
        // クラスのチェック
        if (!$handler) {
            return $this->endWithHttpError(404);
        }
        
        // バージョン番号に合ったメソッド名を取得する。
        $method_name = $this->getVersionMethod($handler, strtolower($_SERVER['REQUEST_METHOD']));
        
        // メソッドのチェック
        if (!method_exists($handler, $method_name)){
            return $this->endWithHttpError(404);
        }
        call_user_func(array($handler, $method_name));
    }

    protected function endWithHttpError($error_code) {
        header('HTTP/1.1 ' . $error_code);
        exit;
    }

    protected function findHandler($request, $response) {
        $url_path = rtrim($this->_get_string, '/');
        $namespace = '\\handler\\';
        if ($url_path === '') {
            $class_prefix = 'index';
        } else {
            $last_pos = strrpos($url_path, '/');
            if ($last_pos === false) {
                $class_prefix = $url_path;
            } else {
                $namespace .= str_replace('/', '\\', substr($url_path, 0, $last_pos + 1));
                $class_prefix = substr($url_path, $last_pos + 1);
            }
        }

        $class_prefix = StringUtility::toCamel($class_prefix, StringUtility::CAMELCASE_UCFIRST);
        $class_name = $namespace . $class_prefix . 'Handler';

        if (!class_exists($class_name)) {
            $this->_log->debug('handler not found: ' . $class_name);
            return;
        }
        
        $handler = new $class_name($this->_log, $request, $response);
        return $handler;
    }
    
    // バージョン情報をURLから取得します。
    protected function findVersion() {
        // URLの一番始めはバージョン番号となる。
        $url_list = explode('/', $this->_get_string, 2);
        $this->_get_string = $url_list[1];
        return $url_list[0];
    }
    
    // IDがあればIDをセットする。
    protected function setRequestId($request) {
        $last_pos = strrpos($this->_get_string, '@');
        if ($last_pos) {
            // IDがある場合はその値を取得する。
            $request->setId(substr($this->_get_string, $last_pos + 1));
            // URLからは排除する。
            $this->_get_string = substr($this->_get_string, 0, $last_pos);
        }
    }
    
    /**
     * 受け取ったメソッド名をバージョン番号に適したメソッドに置き換える。
     */
    protected function getVersionMethod($handler, $method) {
        // キャッシュとして使用する変数を定義
        $class_method_map = NULL;
        
        // APCの中身を削除する場合
//            apc_clear_cache('user');
        
        // APCを使用する場合はAPCにデータが格納されているかをチェックします。
        if (ConfigRepository::load('config')->dig('application.apc.use')) {
            // バージョンによるメソッド振り分け用のキャッシュデータを取得する。
            $version_methods = apc_fetch('version_methods');
            
            // キャッシュが存在する場合はクラス名をキーとしたデータを取得する。
            if (isset($version_methods) && isset($version_methods[get_class($handler)])) {
                $class_method_map = $version_methods[get_class($handler)];
            }
        }
        
        // 設定ファイルからデフォルトバージョンを取得します。
        $default_version = ConfigRepository::load('config')->dig('application.api.default_version');;
        
        // キャッシュが存在しない場合はメソッド名を取得する。
        if (!isset($class_method_map)) {
            // クラス内部のメソッドを格納するエリアを初期化
            $class_method_map = $this->getClassMethodMap($handler, $default_version);
            
            // APCへの格納を行う場合
            if (ConfigRepository::load('config')->dig('application.apc.use')) {
                // 格納されているデータを取得します。
                $version_methods = apc_fetch('version_methods');
                
                // 格納されているデータが無い場合は生成する。
                if (!isset($version_methods)) {
                    $version_methods = array();
                }
                
                // クラス名をキーにマップを作成します。
                $version_methods[get_class($handler)] = $class_method_map;
                
                // APCに登録します。
                apc_delete('version_methods');
                apc_add('version_methods', $version_methods);
            }
        }
        
        // 現在のメソッドを退避させる。
        $ret_method = $method;
        
        // バージョンを取得します。
        $version = NULL;
        if ($this->_version == 'latest') {
            // 最新バージョンを取得します。
            $version = '999.999.999';
        } else {
            // はじめの文字以外を取得します。
            $version = $this->versionFormat(substr($this->_version, 1));
        }
        
        // バージョン名にあったメソッドが存在するかチェックします。
        foreach ($class_method_map[$method] as $version_name => $method_name) {
            // 指定のバージョンが対象となっている場合にバージョン別のメソッドの存在チェックを行う。
            if (version_compare($version, $version_name, '>=')) {
                // バージョン名をメソッドに合わせます。
                $ret_method = $method_name;
            }
        }
        return $ret_method;
    }

    protected function createRequest() {
        return new Request();
    }
    
    protected function createResponse() {
        return new Response();
    }
    
    protected function createResponseRenderer() {
        return new ResponseRenderer();
    }

    /**
     * クラスの中に存在するメソッドをget,post,put,deleteでマピングして返します。
     * 
     * @param class $class_instance 探すクラスのインスタンス 
     * @param type $default_version デフォルトバージョン情報
     * @return array メソッドマップ
     */
    private function getClassMethodMap($class_instance, $default_version) {
        // クラス内部のメソッドを格納するエリアを初期化
        $class_method_map = array();

        // 指定クラス内部に存在するメソッドを取得します。
        $methods = get_class_methods($class_instance);

        // 取得したメソッドをget,post,put,delete毎に分ける。(※小文字のみを許容する。)
        $method_types = array('get', 'post', 'put', 'delete');
        foreach ($methods as &$mtd) {
            foreach ($method_types as &$method_type) {
                // 冒頭の文字がマッチした場合に格納する。
                if (strpos($mtd, $method_type) === 0) {
                    // バージョンの初期値を定義します。
                    $version_str = $default_version;

                    // バージョン番号がある場合はバージョン番号を取得します。
                    $varsion_index = strpos($mtd, 'Ver');
                    if ($varsion_index) {
                        // バージョン番号の接頭辞は3文字なのでそちらを省いたメソッド名を取得します。
                        $version_str = $this->versionFormat(str_replace('_', '.', substr($mtd, $varsion_index + 3)));
                    }

                    // HTTP_METHODに合わせたハッシュを用意する。
                    if (!isset($class_method_map[$method_type])) {
                        $class_method_map[$method_type] = array();
                    }

                    // データを格納します。
                    $class_method_map[$method_type][$version_str] = $mtd;
                }
            }
        }
        
        // クラスのメソッドマップを返します。
        return $class_method_map;
    }
    
    // バージョン番号が不足しているものに関しては不足箇所を足し込みを行う。
    // これを行わないと、1.1 と 1.1.0での比較が正しく行われない。
    private function versionFormat($version) {
        $version_count = substr_count($version, '.');
        if ($version_count < 2) {
            return $version.'.0';
        } else if ($version_count < 1) {
            return $version.'.0.0';
        }
        return $version;
    }
}

/**
 *  ログ呼び出しで落ちないようにするためのダミークラス。
 *  処理は何もしていない。(PSR3に準拠して作成してます。)
 */
class LoggerDummy {
    public function emergency($message, array $context = array()) {}
    public function alert($message, array $context = array()) {}
    public function critical($message, array $context = array()) {}
    public function error($message, array $context = array()) {}
    public function warning($message, array $context = array()) {}
    public function notice($message, array $context = array()) {}
    public function info($message, array $context = array()) {}
    public function debug($message, array $context = array()) {}
    public function log($level, $message, array $context = array()) {}
}
