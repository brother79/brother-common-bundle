<?php
namespace Brother\CommonBundle\Route;

class AppBreadcrumbsItem {
	public $url;
    public $title;
    public $name;
    public $routeName;
	public $params = [];

	public function toArray()
	{
		return ['url' => $this->url, 'params' => $this->params];
	}

	public function setParams($params)
	{
		$this->params = is_object($params) ? $params->toArray(false) : $params;
	}
}