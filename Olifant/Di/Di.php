<?php
namespace Olifant\Di;

use Closure;
use ArrayAccess;
use Olifant\Di\Container;
use Olifant\Di\ContainerException;

class Di extends Container implements ArrayAccess
{
	public function __construct()
	{
		parent::__construct();
	}

	public function offsetExists($key)
	{
		return $this->has($key);
	}

	public function offsetGet($key)
	{
		return $this->make($key);
	}

	public function offsetSet($key, $value)
	{
		if (null === $key) {
			throw new ContainerException('Cannot register empty key');
		}

		if (! $value instanceof Closure) {
			$value = function () use ($value) {
				return $value;
			};
		}

		$this->bind($key, $value);
    }

    public function offsetUnset($key)
	{
		$this->forget($key);
	}
}
?>