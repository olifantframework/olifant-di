<?php
namespace Olifant\Di;

use Olifant\Di\InjectionAwareTrait;

abstract class ServiceProvider
{
	use InjectionAwareTrait;

	protected $defer = false;
    protected $provides = [];

	abstract public function register();

	public function boot() {}
}
?>