<?php

namespace Supsign\LaravelOutlookRest;

use Config;
use Exception;

class OutlookRestApi
{
    protected
    	$ch = null,
        $endpoint = '',
        $endpoints = array(),
        $request = array(),
        $response = null,
        $responseKey = null,
        $responseRaw = array(),
        $step = 100,
        $url = null;

	public function __construct() 
	{
		return $this;
	}

	public function clearResponse()
	{
		$this->response = null;
		$this->responseRaw = array();
		$this->responseKey = null;

		return $this;
	}

	protected function clearRequestData() 
	{
		foreach ($this->request AS $key => $value) {
			unset($this->request[$key]);
		}

		return $this;
	}

	protected function createRequest($method = 'GET') 
	{
		$this->ch = curl_init();

		if ($this->endpoint) {
			curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($this->ch, CURLOPT_USERPWD, $this->login.':'.$this->password);
		}

		curl_setopt($this->ch, CURLOPT_URL, $this->url.$this->endpoint.$this->getRequestString());
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);

		if (strtoupper($method) === 'POST') {
			curl_setopt($this->ch, CURLOPT_POST, true);
		} else
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);

		return $this;
	}

	public function getEndpoint() {
		return $this->endpoint;
	}

	protected function getRequestString()
	{
		if (!$this->request) {
			return '';
		}

		foreach ($this->request AS $key => $value) {
			$pairs[] = implode('=', [$key, $value]);
		}

		return '?'.implode('&', $pairs);
	}

    public function getResponse() 
    {
    	if (!$this->endpoint) {
    		throw new Exception('no endpoint specified', 1);
    	}

    	if (!$this->response) {
    		$this->sendRequests();
    	}

    	return $this->response;
    }

	protected function newCall() {
		return $this
			->clearRequestData()
			->clearResponse();
	}


	protected function sendRequest()
	{
		$this->createRequest();
		$this->setResponse(json_decode(curl_exec($this->ch)));
		curl_close($this->ch);

		return $this;
	}

    protected function sendRequests()
    {
    	do {
    		$this->sendRequest();

			if (!isset($this->request['startAt'])) {
				$this->request['startAt'] = $this->step;
			} else {
				$this->request['startAt'] += $this->step;
			}
    	} while (!$this->requestFinished);

    	$this->response = $this->responseRaw;

    	return $this;
    }

	public function setEndpoint($endpoint) {
		$this->endpoint = $endpoint;

		return $this;
	}

    protected function setRequestData(array $data)
    {
    	$this
    		->clearRequestData()
    		->request = $data;

    	return $this;
    }

    protected function setResponse($response) 
    {
    	$this->requestFinished = true;
    	$this->responseRaw = isset($this->responseKey) ? $response->{$this->responseKey} : $response;

		return $this;
    }
}