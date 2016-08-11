<?php
namespace Olifant\Di;

use Olifant\Di\Container;

class ContextBinder
{
	protected $container;
	protected $concrete;
	protected $needs;

	public function __construct(Container $container, $concrete)
	{
		$this->container = $container;
		$this->concrete = $concrete;
	}

	public function needs($abstract)
	{
		$this->needs = $abstract;

		return $this;
	}

	public function give($implementation)
	{
		return $this->container->context(
			$this->concrete,
			$this->needs,
			$implementation
		);
	}
}
?>