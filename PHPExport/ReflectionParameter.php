<?php
/**
 * Export class for a function/method parameter
 *
 * @author hollodotme
 */

namespace hollodotme\Helpers\PHPExport;

/**
 * Class ReflectionParameter
 *
 * @package hollodotme\Helpers\PHPExport
 */
class ReflectionParameter
{

	/**
	 * @var \ReflectionParameter
	 */
	protected $_parameter;

	/**
	 * Constructor
	 *
	 * @param \ReflectionParameter $parameter
	 */
	public function __construct( \ReflectionParameter $parameter )
	{
		$this->_parameter = $parameter;
	}

	public function exportCode()
	{
		$default_value = null;
		if ( $this->_parameter->isDefaultValueAvailable() )
		{
			$default_value = $this->_parameter->getDefaultValue();
			if ( is_scalar( $default_value ) && !is_numeric( $default_value ) )
			{
				$default_value = "'{$default_value}'";
			}
		}
		elseif ( $this->_parameter->isOptional() )
		{
			$default_value = 'NULL';
		}

		return sprintf(
			'%s%s$%s%s',
			$this->_parameter->getClass() ? "{$this->_parameter->getClass()->getName()} " : '',
			$this->_parameter->isPassedByReference() ? '&' : '',
			$this->_parameter->getName(),
			$default_value ? " = {$default_value}" : ''
		);
	}
}
 