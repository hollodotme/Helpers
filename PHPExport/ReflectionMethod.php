<?php
/**
 * Export class for a method
 *
 * @author hollodotme
 */

namespace hollodotme\Helpers\PHPExport;

/**
 * Class ReflectionMethod
 *
 * @package hollodotme\Helpers\PHPExport
 */
class ReflectionMethod
{

	/**
	 * @var \ReflectionMethod
	 */
	protected $_method;

	/**
	 * Constructor
	 *
	 * @param \ReflectionMethod $method
	 */
	public function __construct( \ReflectionMethod $method )
	{
		$this->_method = $method;
	}

	/**
	 * Exports the PHP code
	 *
	 * @return string
	 */
	public function exportCode()
	{
		$modifiers = \Reflection::getModifierNames( $this->_method->getModifiers() );
		$params = array();

		// Export method's parameters
		foreach ( $this->_method->getParameters() as $param )
		{
			$reflection_parameter = new ReflectionParameter( $param );
			$params[] = $reflection_parameter->exportCode();
		}

		return sprintf(
			'%s function %s(%s) {}',
			join( ' ', $modifiers ),
			$this->_method->getName(),
			join( ', ', $params )
		);
	}
}
 