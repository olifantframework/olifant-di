<?php
namespace Olifant\Di;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionException;
use Olifant\Di\Container;
use Olifant\Di\ContainerException;

class Reflector
{
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	private function buildContext(ReflectionParameter $parameter)
	{
		$parameterClass = $parameter->getClass();

		if ($parameterClass) {
			$abstract = $parameterClass->getName();
		} else {
			$parameterName = $parameter->getName();
			$abstract = '$' . $parameterName;
		}

		return $abstract;
	}

	private function checkContext($context, ReflectionParameter $parameter)
	{
		$abstract = $this->buildContext($parameter);

		return $this->container->isOverriden($context, $abstract);
	}

	private function getContext($context, ReflectionParameter $parameter)
	{
		$abstract = $this->buildContext($parameter);

		return $this->container->getOverride($context, $abstract);
	}

	public function buildArguments($context, array $parameters, array $additional)
	{
		$callParameters = array();
		foreach ($parameters as $parameter) {
			$parameterName = $parameter->getName();

			if (array_key_exists($parameterName, $additional)) {
				$callParameters[] = $additional[$parameterName];
			} else {
				$hasContext = $this->checkContext($context, $parameter);

				if ($hasContext) {
					$concreteContext = $this->getContext($context, $parameter);
					$callParameters[] = $this->container->resolve(
						$concreteContext,
						[],
						$parameter->getClass() ? false : true
					);
				} else if ($parameter->getClass()) {
					$classname = $parameter->getClass()->getName();
					$callParameters[] = $this->container->make($classname);
				} else {
					if ($parameter->isDefaultValueAvailable()) {
						$callParameters[] = $parameter->getDefaultValue();
					}
				}
			}
		}

		return $callParameters;
	}

	public function packClosure($instance, $method)
	{
		return (
			(new ReflectionClass($instance))
			->getMethod($method)
			->getClosure($instance)
		);
	}

	public function reflect($key, array $additional = [])
	{
		try {
			if (is_array($key)) {
				list($instance, $method) = $key;
				$key = $this->packClosure($instance, $method);
			}

			if ($key instanceof Closure) {
				$reflection = new ReflectionFunction($key);

				$parameters = $reflection->getParameters();
				$arguments = $this->buildArguments('Closure', $parameters, $additional);

				return call_user_func_array($key, $arguments);
			} else {
				if (false !== strpos($key, '::')) {
					list($class, $method) = explode('::', $key);
				} else {
					$class = $key;
				}

				$reflection = new ReflectionClass($class);

				if (false == $reflection->isInstantiable()) {
					throw new ContainerException(
						sprintf('%s is not instantiable', $class)
					);
				}

				$constructor = $reflection->getConstructor();
				if ($constructor) {
					$parameters = $constructor->getParameters();
					$arguments = $this->buildArguments(
						$class,
						$parameters,
						$additional
					);
					$instance = $reflection->newInstanceArgs($arguments);
				} else {
					$instance = $reflection->newInstance();
				}

				$traits = $reflection->getTraitNames();
				if (in_array('Olifant\Di\InjectionAwareTrait', $traits)) {
					$instance->setApp($this->container->getApp());
				}

				if (isset($method)) {
					$method = $reflection->getMethod($method);
					$parameters = $method->getParameters();
					$methodArguments = $this->buildArguments(
						$class,
						$parameters,
						$additional
					);

					return $method->invokeArgs($instance, $methodArguments);
				} else {
					return $instance;
				}
			}
		} catch (ReflectionException $e) {
			throw new ContainerException(
				$e->getMessage(),
				$e->getCode()
			);
		}
	}
}
?>