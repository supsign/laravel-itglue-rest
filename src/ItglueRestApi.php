<?php

namespace Supsign\LaravelItglueRest;

use CurlHandle;
use Config;
use Exception;
use stdClass;

class ItglueRestApi
{
	protected ?CurlHandle $ch = null;
	protected string $endpoint = '';
	protected int|string $endpointId = '';
	protected array|stdClass $response = [];
	protected array|stdClass $request = [];
	protected string $requestType = '';
    protected ?string $token = null;
    protected ?string $url = null;

	public function __construct()
	{
		$this->token = env('ITGLUE_REST_TOKEN');
		$this->url = env('ITGLUE_REST_URL');
	}

	public function clearEndpoint(): self
	{
		return $this->setEndpoint('');
	}

	public function clearResponse(): self
	{
		$this->response = [];

		return $this;
	}

	protected function clearRequestData(): self
	{
		$this->request = [];

		return $this;
	}

	public function createOrganizations(array $data)
	{
		return $this
			->newCall('POST')
			->setEndpoint('organizations')
			->setRequestData($data)
			->getResponse();
	}

	protected function createRequest(): self
	{
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Content-Type: application/vnd.api+json', 'x-api-key: '.$this->token]);

		$url = $this->url.$this->getEndpoint();

		switch (strtoupper($this->requestType)) {
			case 'GET':
				$url .= $this->getRequestString();
				$this->clearRequestData();
				break;

			case 'POST':
				curl_setopt($this->ch, CURLOPT_POST, true);
				break;

			default:
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->requestType);				
				break;
		}

		if (!empty($this->request)) {
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->getRequestJson()); 
		}

		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);

		return $this;
	}

	// public function deleteOrganizations(array $ids)
	// {

	// }

	protected function getEndpoint()
	{
		if ($this->endpointId) {
			return $this->endpoint.'/'.$this->endpointId;
		}

		return $this->endpoint;
	}

	public function getOrganization(int $id): stdClass
	{
		return $this
			->newCall()
			->setEndpoint('organizations', $id)
			->getResponse();
	}

	public function getOrganizations($filters = []): array
	{
		return $this
			->newCall()
			->setEndpoint('organizations')
			->setRequestData($filters)
			->getResponse();
	}

	protected function getRequestJson(): string
	{
		if (!$this->request) {
			return '';
		}

		$request = ['data' => [
			'type' => $this->endpoint,
			'attributes' => []
		]];

		foreach ($this->request AS $key => $value) {
			$request['data']['attributes'][$key] = $value;
		}

		return json_encode($request);
	}

	protected function getRequestString(): string
	{
		if (!$this->request) {
			return '';
		}

		foreach ($this->request AS $key => $value) {
			$pairs[] = implode('=', [$key, urlencode($value)]);
		}

		return '?'.implode('&', $pairs);
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

	protected function newCall($method = 'GET'): self
	{
		return $this
			->clearEndpoint()
			->clearRequestData()
			->clearResponse()
			->setRequestType($method);
	}

	public function patchOrganizations(int $id, array $data): array|stdClass
	{
		return $this
			->newCall('PATCH')
			->setEndpoint('organizations', $id)
			->setRequestData($data)
			->getResponse();
	}

	protected function sendRequest(): self
	{
		$this->createRequest();
		$this->response = json_decode(curl_exec($this->ch));
		curl_close($this->ch);

		return $this;
	}

	public function setEndpoint($endpoint, $id = ''): self
	{
		$this->endpoint = $endpoint;
		$this->endpointId = $id;

		return $this;
	}

    protected function setRequestData(array $data): self
    {
    	$this
    		->clearRequestData()
    		->request = $data;

    	return $this;
    }

	public function setRequestType($requestType): self
	{
		$this->requestType = $requestType;

		return $this;
	}
}