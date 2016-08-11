<?php
namespace Olifant\Di;

use Olifant\Di\Container;

abstract class Facade
{
	protected static $container;

	abstract public static function getKey();

	public static function setContainer(Container $container)
	{
		self::$container = $container;
	}

	protected static function getFacadeRoot()
	{
		return self::$container[static::getKey()];
	}

	public static function __callStatic($method, $args)
	{
		$instance = static::getFacadeRoot();

		return call_user_func_array([$instance, $method], $args);
	}
}
?>