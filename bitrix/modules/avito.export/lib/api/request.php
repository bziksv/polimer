<?php
namespace Avito\Export\Api;

use Bitrix\Main;
use Avito\Export\Psr;
use Avito\Export\Utils;
use Avito\Export\Config;

/**
 * @template T
 */
abstract class Request implements Psr\Logger\LoggerAwareInterface
{
	protected $logger;

	public function __construct()
	{
		$this->logger = new Utils\Logger\NullLogger();
	}

	public function setLogger(Psr\Logger\LoggerInterface $logger) : void
	{
		$this->logger = $logger;
	}

	abstract public function url() : string;

	abstract public function method() : string;

	abstract public function query() : ?array;

	/** @return T */
	public function execute() : Response
	{
		$transport = $this->buildTransport();

		$this->sendRequest($transport);

		$data = $this->parseResponse($transport);
		$this->logResponse($transport, $data);

		$this->validateResponse($data, $transport);

		return $this->buildResponse($data);
	}

	protected function buildTransport() : Main\Web\HttpClient
	{
		$transport = new Main\Web\HttpClient([
			'version' => '1.1',
			'socketTimeout' => 30,
			'streamTimeout' => 30,
			'redirect' => true,
			'redirectMax' => 5,
		]);
		$transport->setHeader(
			'X-Module-Version',
			sprintf('BitrixAvitoModule-%s', Main\ModuleManager::getVersion(Config::getModuleName()))
		);

		return $transport;
	}

	protected function sendRequest(Main\Web\HttpClient $transport) : void
	{
		$method = $this->method();
		$url = $this->url();
		$query = $this->query();
		$queryBody = null;

		if ($method === Main\Web\HttpClient::HTTP_GET)
		{
			$queryString = http_build_query($query);

			if ($queryString !== '')
			{
				$url .= (mb_strpos($url, '?') === false ? '?' : '&') . $queryString;
			}
		}
		else if ($query !== null)
		{
			$queryBody = $this->encodeBody($query, $transport);
		}

		$transport->query($method, $url, $queryBody);
	}

	protected function encodeBody($query, Main\Web\HttpClient $transport)
	{
		$transport->setHeader('Content-type', 'application/json');

		return Main\Web\Json::encode($query);
	}

	protected function parseResponse(Main\Web\HttpClient $transport)
	{
		$status = $transport->getStatus();
		$response = $transport->getResult();

		if ($response === '')
		{
			if ($status === 200) { return null; }

			$errors = $transport->getError();
			$message = !empty($errors)
				? sprintf('[%s] %s', key($errors), reset($errors))
				: null;

			throw new Exception\HttpError($status, $message);
		}

		if (
			mb_strpos($response, '{') === 0
			|| mb_strtolower($transport->getContentType()) === 'application/json'
		)
		{
			try
			{
				return $this->parseJson($response);
			}
			catch (Exception\ParseError $exception)
			{
				if ($status === 200) { throw $exception; }

				throw new Exception\HttpError($status, $exception->getMessage(), $exception);
			}
		}

		return $response;
	}

	protected function logResponse(Main\Web\HttpClient $transport, $response) : void
	{
		if (Config::getOption('api_log', 'N') !== 'Y') { return; }

		$path = dirname(Config::getModulePath()) . '/log/api.txt';
		CheckDirPath($path);

		$message = sprintf("%s\n%s\n---\n%s\n%s\n----------\n",
			sprintf(
				'%s %s HTTP %s',
				$this->method(),
				$transport->getEffectiveUrl(),
				$transport->getStatus()
			),
			$this->formatLogVariable($this->query()),
			$this->formatLogVariable($response),
			Utils\BackTrace::format(Main\Diag\Helper::getBackTrace(20, DEBUG_BACKTRACE_IGNORE_ARGS, 2))
		);

		if (class_exists(Main\Diag\FileLogger::class))
		{
			$logger = new Main\Diag\FileLogger($path, 3000000);
			$logger->info($message);
		}
		else
		{
			if (file_exists($path) && filesize($path) > 3000000)
			{
				rename($path, $path . '.old');
			}

			file_put_contents($path, $message, FILE_APPEND);
		}
	}

	protected function formatLogVariable($variable)
	{
		return is_string($variable) || $variable === null
			? $variable
			: Main\Web\Json::encode($variable, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}

	protected function parseJson(string $response)
	{
		try
		{
			return Main\Web\Json::decode($response);
		}
		catch (Main\SystemException $exception)
		{
			throw new Exception\ParseError($exception);
		}
	}

	protected function validateResponse($data, Main\Web\HttpClient $transport) : void
	{
		$this->validationQueue($data, $transport)->run();
	}

	protected function validationQueue($data, Main\Web\HttpClient $transport) : Validator\Queue
	{
		return (new Validator\Queue())
			->add(new Validator\FormatArray($data, $transport))
			->add(new Validator\MessageError($data, $transport))
			->add(new Validator\ResponseError($data, $transport));
	}

	/**
	 * @param $data
	 *
	 * @return T
	 */
	abstract protected function buildResponse($data) : Response;
}
