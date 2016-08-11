<?php
namespace Olifant\Di;

use Olifant\Kernel\KernelException;

class ContainerException extends KernelException
{
	public function __construct($message, $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
?>