<?php
namespace Olifant\Di;

use Olifant\Support\HashMap;
use Olifant\Di\ContainerException;

class PersistentStorage
{
	protected static $storage = [];
	protected static $watch = ['aliases','instances','registry','singletones'];

	public function has($key)
	{
		return in_array($key, $this->keys(), true);
	}

	public function keys()
	{
		$keys = [];
		foreach (self::$storage as $key => $value) {
			if (in_array($key, self::$watch)) {
				$keys = array_merge($keys, $value->keys());
			}
		}

		return $keys;
	}

	public function forget($key)
	{
		foreach (self::$storage as $key => $value) {
			if (in_array($key, self::$watch)) {
				$value->del($key);
			}
		}
	}

	public function flush()
	{
		self::$storage = [];
	}

	public function __get($key)
	{
		if (!isset(self::$storage[$key])) {
			self::$storage[$key] = new HashMap;
		}

		return self::$storage[$key];
	}
}
?>