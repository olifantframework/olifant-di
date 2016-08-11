<?php
namespace Olifant\Di;

trait InjectionAwareTrait
{
	protected $app;

	public function setApp($app)
	{
		$this->app = $app;
	}

	public function getApp()
	{
		return $this->app;
	}
}
?>