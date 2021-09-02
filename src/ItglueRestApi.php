<?php

namespace Supsign\LaravelItglueRest;

use CurlHandle;
use Config;
use Exception;
use stdClass;

class ItglueRestApi
{
	protected ?CurlHandle $ch = null;
	protected ?string $endpoint = null;
	protected array $request = [];
	protected ?stdClass $response = null;
    protected ?string $token = null;
    protected ?string $url = null;

	public function __construct()
	{
		$this->token = env('ITGLUE_REST_TOKEN');
		$this->url = env('ITGLUE_REST_URL');
	}

	public function clearResponse(): self
	{
		$this->response = null;

		return $this;
	}

	protected function clearRequestData(): self
	{
		foreach ($this->request AS $key => $value) {
			unset($this->request[$key]);
		}

		return $this;
	}

	protected function createRequest($method = 'GET'): self
	{
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Content-Type: application/vnd.api+json', 'x-api-key: '.$this->token]);
		curl_setopt($this->ch, CURLOPT_URL, $this->url.$this->endpoint.$this->getRequestString());
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);

		if (strtoupper($method) === 'POST') {
			curl_setopt($this->ch, CURLOPT_POST, true);
		} else {
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
		}

		return $this;
	}

	public function getOrganizations(): array
	{
		return $this
			->newCall()
			->setEndpoint('organizations')
			->getResponse();
	}

	protected function getRequestString(): string
	{
		if (!$this->request) {
			return '';
		}

		foreach ($this->request AS $key => $value) {
			$pairs[] = implode('=', [$key, $value]);
		}

		return '?'.implode('&', $pairs);
	}

	protected function newCall(): self
	{
		return $this
			->clearRequestData()
			->clearResponse();
	}

    public function getResponse(): array|stdClass
    {
    	if (!$this->endpoint) {
    		throw new Exception('no endpoint specified', 1);
    	}

    	if (!$this->response) {
    		$this->sendRequest();
    	}

    	if (isset($this->response->data)) {
    		return $this->response->data;
    	}

    	return $this->response;
    }

	protected function sendRequest(): self
	{
		$this->createRequest();
		$this->response = json_decode(curl_exec($this->ch));
		curl_close($this->ch);

		return $this;
	}

	public function setEndpoint($endpoint): self
	{
		$this->endpoint = $endpoint;

		return $this;
	}

	public function test()
	{
		return $this->getOrganizations();
	}
}