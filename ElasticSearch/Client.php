<?php


namespace Brother\CommonBundle\ElasticSearch;


use Elasticsearch\ClientBuilder;

class Client {

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * Client constructor.
     *
     * @param string $hosts
     */
    public function __construct($hosts='elasticsearch:9200') {
        $clientBuilder = ClientBuilder::create();
        if (is_string($hosts)) {
            $hosts = explode(',', $hosts);
        }
        $clientBuilder->setHosts($hosts);
        $this->client = $clientBuilder->build();
    }

    /**
     * @param array $eParams
     *
     * @return array|callable
     */
    public function deleteByQuery(array $eParams) {
        return $this->client->deleteByQuery($eParams);
    }

    /**
     * @return \Elasticsearch\Namespaces\IndicesNamespace
     */
    public function indices() {
        return $this->client->indices();
    }

    /**
     * @param array $array
     *
     * @return array|callable
     */
    public function index(array $array) {
        return $this->client->index($array);
    }

    /**
     * @param array $params
     *
     * @return array|callable
     */
    public function bulk(array $params){
        return $this->client->bulk($params);
    }

}