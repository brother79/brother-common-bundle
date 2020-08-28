<?php


namespace Brother\CommonBundle\ElasticSearch;


use Elasticsearch\ClientBuilder;

class Client {

    private $client;

    public function __construct($hosts='elasticsearch:9200') {
        $clientBuilder = ClientBuilder::create();
        if (is_string($hosts)) {
            $hosts = explode(',', $hosts);
        }
        $clientBuilder->setHosts($hosts);
        $this->client = $clientBuilder->build();
    }

    public function deleteByQuery(array $eParams) {
        return $this->client->deleteByQuery($eParams);
    }

    public function indices() {
        return $this->client->indices();
    }

    public function index(array $array) {
        return $this->client->index($array);
    }

}