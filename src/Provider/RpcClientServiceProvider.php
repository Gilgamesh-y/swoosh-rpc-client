<?php

namespace Src\RPCClient\Provider;

use Src\RPCClient\RPCClient;
use Src\Core\AbstractProvider;
use Src\RPCClient\ConnectionFactory;

class RpcClientServiceProvider extends AbstractProvider
{
    public function register()
    {
        $this->app->set('rpc_client', function () {
            return new RPCClient;
        });

        $this->app->set('rpc_connection', function () {
            return (new ConnectionFactory)->createConnection($this->app->get('config')->get('app.rpc_client'));
        });
    }
}