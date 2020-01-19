<?php

namespace Src\RPCClient\Connection;

use Src\RPCServer\Connections\ConsulConnection;
use Swoole\Coroutine\Client;

class CoConnection extends Connection
{
    /**
     * The service's remote host
     *
     * @var string
     */
    protected $target_host = '127.0.0.1';

    /**
     * The service's remote port
     *
     * @var integer
     */
    protected $target_port = 80;

    public function makeConnection(string $service)
    {
        $connection = new Client(SWOOLE_SOCK_TCP);
        if ($this->server_stub instanceof ConsulConnection) {
            $this->selectRemoteService($service);
        }
        $connection->set($this->client->getSetting());
        if (!$connection->connect($this->target_host, $this->target_port)) {
            throw new \Exception('rpc server_stub连接'.$this->target_host.':'.$this->target_port.'失败');
        }

        $this->connection = $connection;
    }

    /**
     * Get catalog of the service
     * @return array
     */
    public function getRemoteServices($service_name)
    {
        $services = $this->server_stub->services($service_name, true);
        if (empty($services)) {
            throw new \Exception('rpc server_stub has not find the services named '.$service_name);
        }

        return $services;
    }

    /**
     * Select a remote service
     *
     * @param int|string $service_name
     * @return void
     */
    public function selectRemoteService($service_name)
    {
        $services = $this->getRemoteServices($service_name);
        $key = array_rand($services);
        $service = $services[$key];
        $this->target_host = $service['ServiceAddress'];
        $this->target_port = $service['ServicePort'];
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
        $res = $this->connection->recv((float)2);

        if ($res === false) {
            throw new \Exception('获取数据失败', $this->connection->errCode);
        }
        $res = bin_to_str($res);
        $res_len_arr = explode("-", $res);
        $status_len = $res_len_arr[0];
        $code_len = $res_len_arr[1];
        $res = substr($res, strlen($status_len) + strlen($code_len) + 2);
        $res = unpack("A{$status_len}status/L{$code_len}code/A*data", $res);
        $res['data'] = json_decode($res['data'], true);

        return $res;
    }
}