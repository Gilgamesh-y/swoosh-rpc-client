<?php

namespace Src\RPCClient\Connection;

use Swoole\Client;

class SyncConnection extends Connection
{
    public function makeConnection()
    {
        $connection = new Client(SWOOLE_SOCK_TCP);

        $connection->set($this->client->getSetting());
        if (!$connection->connect($this->server_stub->getServerStubHost(), $this->server_stub->getServerStubPort())) {
            throw new \Exception('rpc server_stub连接'.$this->server_stub->getServerStubHost().':'.$this->server_stub->getServerStubPort().'失败');
        }

        $this->connection = $connection;
    }
    
    public function close()
    {
        $this->connection->close();
    }

    /**
     * @return array
     */
    public function recv(): array
    {
        $res = $this->connection->recv();

        if ($res === false) {
            throw new \Exception('获取数据失败', $this->connection->errCode);
        }
        $res_len_arr = explode("-", $res);
        $status_len = $res_len_arr[0];
        $code_len = $res_len_arr[1];
        $res = substr($res, strlen($status_len) + strlen($code_len) + 2, -1);
        $res = unpack("A{$status_len}status/A{$code_len}code/A*data", $res);
        $res['data'] = json_decode($res['data'], true);

        return $res;
    }
}