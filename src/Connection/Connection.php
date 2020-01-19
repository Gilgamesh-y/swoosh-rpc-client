<?php

namespace Src\RPCClient\Connection;

use Src\RPC\RpcProtocol;
use Src\RPC\Packet\Encoder;
use Src\RPCClient\RPCClient;
use Src\RPCClient\Contract\ConnectionInterface;
use Src\RPCServer\Contract\ConnectionInterface as ServerConnectionInterface;

abstract class Connection implements ConnectionInterface
{
/**
     * @var Client
     */
    protected $connection;

    /**
     * @var RPCClient
     */
    protected $client;

    /**
     * @var ServerConnectionInterface $server_stub
     */
    protected $server_stub;

    /**
     * @param RPCClient $client
     * @param ServerConnectionInterface $server_stub
     * @return Connection
     */
    public function init(RPCClient $client, ServerConnectionInterface $server_stub): Connection
    {
        $this->client = $client;
        $this->server_stub = $server_stub;

        return $this;
    }

    /**
     * @param string $service The name of service
     * @param $proto The data of send to server
     *
     * @return bool
     */
    public function send(string $service, $proto = ''): bool
    {
        $this->makeConnection($service);
        $rpcProtocol = Encoder::rpcEncode(
            RpcProtocol::init($service, '\\' . get_class($proto), $proto instanceof \Google\Protobuf\Internal\Message ? $proto->serializeToString() : '')
        );

        $res = $this->connection->send($rpcProtocol);

        if (!$res) {
            throw new \Exception($this->connection->errCode);
        }

        return (bool)$res;
    }
}