phpkg-rest
==========

インストール
----------
composerからこちらを叩く事でpokelaboのRESTフレームワークがインストールされます。

    {
        "require": {
           "apix/autoloader": "1.0.*",
           "pokelabo/rest": "1.0.*"
        },
        "repositories": [
            { "type": "git", "url": "https://github.com/pokelabo/phpkg-rest.git" },
            { "type": "git", "url": "https://github.com/pokelabo/phpkg-core.git" },
            { "type": "git", "url": "https://github.com/pokelabo/phpkg-http.git" },
            { "type": "git", "url": "https://github.com/pokelabo/phpkg-core-utility.git" }
        ],
        "autoload": {
            "psr-0": {
                "pokelabo": "src/"
            }
        }
    }


#### コンポーザーのインストール

    curl -s https://getcomposer.org/installer | php

こちらのコマンドで`composer.phar`がインストールされます。
同フォルダに`composer.json`を配置し、上記のjsonを記載して

    php composer.phar install

を行ってください。

導入
----------
`composer`でインストールすると`vender/pokelabo/rest/example`以下に`app`フォルダが作成されてますので、こちらを`vender`と同じ場所にコピーしてください。  
`app/www`をDocumentRootに設定して`http://***/v1/rest_test`にアクセスするとjsonでデータが戻ってきます。  
これで導入は完了です。

handler
----------
フレームワークで提供しているのは`handler`のみとなります。  
`AppHandler`は自由に拡張してかまいませんし、他のクラスは全て削除してもらってもかまいません。  
※本フレームワークでは規定クラスは用意せずに、メソッド名に対してのアクセスのみを行ってます。  
※ただし、コンストラクタのインターフェースだけは変えないでください。

メソッド名
----------
メソッド名は以下のルールで記載してください。

* get()
* post()
* put()
* delete()

基本的に定義できるのはこれらのメソッド名だけになります。  
RESTアクセスのHTTPメソッドと同じものにアクセスします。

Versioning
----------
通常は上記のメソッド名だけしか定義できませんが、APIのバージョンに合わせてメソッド名を振り分ける事が出来ます。  
例えば

* getVer1_1()
* getVer2()

のメソッドがある場合はリクエストによって呼び出されるメソッドが変わります。

    /v1.1/hanbler

で呼び出す場合は`getVer1_1`が呼び出されます。

    /v2/handler

で呼び出す場合は`getVer2`が呼び出されます。  
URLのはじめの部分がVersionとなっており、それに対応するメソッドを呼び出します。  
※指定のメソッドが存在しない場合は指定したものより古いバージョンのメソッドを呼び出します。

`get()`のようにバージョン番号を記載していないメソッドは`v1.0.0`として定義されます。  
※デフォルトバージョンはconfig.ymlで書き換える事が出来ます。

リソースのID指定
----------
リソース内のIDにアクセスする場合は以下のアクセスを行う。

    /v1/userr@123

`@`を使用する事で、リソースの中の一意を探し出す事が出来る。  
サーバー内部で取得するには

    $this->_request->getid()

で取得できます。
