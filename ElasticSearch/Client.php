<?php


namespace Brother\CommonBundle\ElasticSearch;


class Client {

    /**
     * @return Client
     * @throws SLExceptionRegistry
     *                            "elasticsearch/elasticsearch": "~7.0",
     */
    public static function getElasticClient() {
        /** @var Client $client */
        $client = self::get('elastic_client');
        if (!$client) {
            $clientBuilder = ClientBuilder::create();
            $config = json_decode(ELASTIC_CONFIG, true);
            if (isset($config['hosts'])) {
                $clientBuilder->setHosts($config['hosts']);
            } else {
                $clientBuilder->setConnectionPool('\Elasticsearch\ConnectionPool\SniffingConnectionPool', []);
            }
            $client = $clientBuilder->build();
            self::set('elastic_client', $client);
        }
        return $client;
    }

}