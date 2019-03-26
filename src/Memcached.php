<?php namespace Framework\Cache;

/**
 * Class Memcached.
 */
class Memcached extends Cache
{
	/**
	 * @var \Memcached
	 */
	protected $memcached;
	/**
	 * @var array
	 */
	protected $configs = [
		[
			'host' => '127.0.0.1',
			'port' => 11211,
			'weight' => 1,
		],
	];

	public function __construct(
		array $configs = [],
		string $prefix = null,
		string $serializer = 'php'
	) {
		parent::__construct($configs, $prefix, $serializer);
		$this->validateConfigs();
		$this->connect();
	}

	public function __destruct()
	{
		if ($this->memcached) {
			$this->memcached->quit();
		}
	}

	protected function validateConfigs() : void
	{
		foreach ($this->configs as $index => $config) {
			if (empty($config['host'])) {
				throw new \OutOfBoundsException(
					"Memcached server host empty on config {$index}"
				);
			}
		}
	}

	public function get(string $key)
	{
		return $this->memcached->get($this->renderKey($key)) ?: null;
	}

	public function set(string $key, $value, int $ttl = 60) : bool
	{
		return $this->memcached->set($this->renderKey($key), $value, $ttl);
	}

	public function delete(string $key) : bool
	{
		return $this->memcached->delete($this->renderKey($key));
	}

	public function flush() : bool
	{
		return $this->memcached->flush();
	}

	protected function connect()
	{
		switch ($this->serializer) {
			case 'igbinary':
				$serializer = \Memcached::SERIALIZER_IGBINARY;
				break;
			case 'json':
				$serializer = \Memcached::SERIALIZER_JSON;
				break;
			case 'msgpack':
				$serializer = \Memcached::SERIALIZER_MSGPACK;
				break;
			case 'php':
			default:
				$serializer = \Memcached::SERIALIZER_PHP;
				break;
		}
		$this->memcached = new \Memcached();
		$this->memcached->setOptions([
			\Memcached::OPT_BINARY_PROTOCOL => true,
			\Memcached::OPT_CONNECT_TIMEOUT => 100,
			\Memcached::OPT_COMPRESSION => true,
			\Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT,
			\Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
			\Memcached::OPT_POLL_TIMEOUT => 100,
			\Memcached::OPT_RECV_TIMEOUT => 100,
			\Memcached::OPT_REMOVE_FAILED_SERVERS => true,
			\Memcached::OPT_RETRY_TIMEOUT => 1,
			\Memcached::OPT_SEND_TIMEOUT => 100,
			\Memcached::OPT_SERIALIZER => $serializer,
			\Memcached::OPT_SERVER_FAILURE_LIMIT => 2,
		]);
		foreach ($this->configs as $configs) {
			$this->memcached->addServer(
				$configs['host'],
				$configs['port'] ?? 11211,
				$configs['weight'] ?? 1
			);
		}
	}
}
