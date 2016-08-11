<?php
namespace Olifant\Di;

use Closure;
use Olifant\App;
use ReflectionClass;
use ReflectionException;
use Olifant\Support\HashMap;
use Olifant\Di\ContainerException;

class Container
{
	use InjectionAwareTrait;

	protected $reflector;
	protected $persistentStorage;

	public function __construct()
	{
		$this->reflector = new Reflector($this);
		$this->persistentStorage = new PersistentStorage;
	}

	public function registerProvider($provider)
	{
		$instance = $this->make($provider);
		$instance->setApp($this->app);
		$instance->register();

		return $instance;
	}

	public function deferProvider($provider, array $provides)
	{
		$this->persistentStorage->deferred[$provider] = $provides;
	}

	public function resolveDeferred($key)
	{
		foreach ($this->persistentStorage->deferred as $provider => $provides) {
			if (in_array($key, $provides)) {
				$this->bootProvider(
					$this->registerProvider($provider, $this->app)
				);

				$this->persistentStorage->deferred->del($provider);
			}
		}
	}

	public function bootProvider($instance)
	{
		die('redo!');
		$this->make(
			$this->reflector->packClosure(
				$instance, 'boot'
			)
		);
	}

	public function keys()
	{
		return $this->persistentStorage->keys();
	}

	public function has($key)
	{
		return $this->persistentStorage->has($key);
	}

	public function resolve($entity, array $additional = [], $raw = false)
	{
		if ($entity instanceof Closure) return $entity($this->app);

		if ($raw) return $entity;

		return $this->make($entity, $additional);
	}

	public function when($concrete)
	{
		return new ContextBinder($this, $concrete);
	}

	public function context($concrete, $needs, $implementation)
	{
		$context = $this->persistentStorage->context;
 		if(!$context->has($concrete)){
			$context[$concrete] = new HashMap;
		}

		$context[$concrete][$needs] = $implementation;

		return $this;
	}

	public function isOverriden($concrete, $needs)
	{
		return (
			$this->persistentStorage->context->has($concrete)
			and isset($this->persistentStorage->context[$concrete][$needs])
		);
	}

	public function getOverride($concrete, $needs)
	{
		return $this->persistentStorage->context[$concrete][$needs];
	}

	private function check($key)
	{
		if ($this->has($key)) {
			throw new ContainerException(
				sprintf('Container already contains key: %s', $key)
			);
		}
	}

	public function instance($key, $resolver)
	{
		$this->check($key);
		$this->persistentStorage->instances[$key] = $resolver;

		return $this;
	}

	public function singleton($key, $resolver)
	{
		$this->check($key);
		$this->persistentStorage->singletones[$key] = $resolver;

		return $this;
	}

	public function bind($key, $resolver)
	{
		$this->check($key);
		$this->persistentStorage->registry[$key] = $resolver;

		return $this;
	}

	public function alias($alias, $abstract)
	{
		$this->check($key);
		$this->persistentStorage->aliases[$alias] = $abstract;

		return $this;
	}

	public function forget($key)
	{
		$this->persistentStorage->forget();
	}

	public function flush()
	{
		return $this->persistentStorage->flush();
	}

	public function refresh($key, $value)
	{

	}

	public function makes()
	{
		$list = [];
		foreach (func_get_args() as $key) {
			$list[] = $this->make($key);
		}

		return $list;
	}

	public function make($key, array $additional = array())
	{
		if (is_array($key)) {
			return $this->reflector->reflect($key);
		}

		if ($key instanceof Closure) {
			return $this->reflector->reflect($key, $additional);
		}

		$this->resolveDeferred($key);

		if ($this->persistentStorage->aliases->has($key)) {
			$key = $this->persistentStorage->aliases[$key];
		}

		if ($this->persistentStorage->instances->has($key)) {
			return $this->persistentStorage->instances[$key];
		}

		if ($this->persistentStorage->registry->has($key)) {
			$registry = $this->persistentStorage->registry[$key];
			$instance = $this->resolve($registry, $additional);

			return $instance;
		} else if ($this->persistentStorage->singletones->has($key)) {
			$singleton = $this->persistentStorage->singletones[$key];
			$instance = $this->resolve($singleton, $additional);
			$this->persistentStorage->instances[$key] = $instance;

			return $instance;
		} else {
			return $this->reflector->reflect($key, $additional);
		}
	}
}
?>