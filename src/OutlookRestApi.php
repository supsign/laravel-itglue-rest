<?php

namespace Supsign\LaravelOutlookRest;

use Config;
use Exception;

class OutlookRestApi
{
    protected
    	$authUrl = null,
    	$ch = null,
    	$clientId = null,
    	$clientSecret = null,
        $endpoint = '',
        $endpoints = array(),
        $request = array(),
        $response = null,
        $responseKey = null,
        $responseRaw = array(),
        $step = 100,
        $token = null,
        $tokenType = null,
        $url = null;

	public function __construct($email) 
	{
		$this->authUrl = env('OUTLOOK_REST_AUTHURL');
		$this->clientId = env('OUTLOOK_REST_LOGIN');
		$this->clientSecret = env('OUTLOOK_REST_PASSWORD');
		$this->url = env('OUTLOOK_REST_URL').$email.'/';

		return $this;
	}

	public function test()
	{

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

	protected function createAccessToken() {
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_URL, $this->authUrl);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($this->ch, CURLOPT_USERPWD, $this->clientId.':'.$this->clientSecret);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&scope=https://outlook.office.com/.default");

		$authResponse = json_decode(curl_exec($this->ch) );

		curl_close($this->ch);

		if (isset($authResponse->error)) {
			throw new Exception($authResponse->error_description, 1);
		}

		$this->token = $authResponse->access_token;
		$this->tokenType = $authResponse->token_type;
		
		return $this;
	}

	protected function createRequest($method = 'GET') 
	{
		if (!$this->token) {
			$this->createAccessToken();
		}

		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: '.$this->tokenType.' '.$this->token]);
		curl_setopt($this->ch, CURLOPT_URL, $this->url.$this->endpoint.$this->getRequestString());
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);

		if (strtoupper($method) === 'POST') {
			curl_setopt($this->ch, CURLOPT_POST, true);
		} else {
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
		}

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

    public function getTasks()
    {
		$this->newCall()->endpoint = 'tasks';
		$this->responseKey = 'value';

		return $this->getResponse();
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

    public function setUser($email) {
    	$this->url = env('OUTLOOK_REST_URL').$email.'/';

    	return $this;
    }
}