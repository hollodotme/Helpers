<?php
/**
 * Export class for a class' property
 *
 * @author hollodotme
 */

namespace hollodotme\Helpers\PHPExport;

/**
 * Class ReflectionProperty
 *
 * @package hollodotme\Helpers\PHPExport
 */
class ReflectionProperty
{

	/**
	 * @var \ReflectionProperty
	 */
	protected $_property;

	/**
	 * Constructor
	 *
	 * @param \ReflectionProperty $property
	 */
	public function __construct( \ReflectionProperty $property )
	{
		$this->_property = $property;
	}

	/**
	 * Exports the PHP code
	 *
	 * @return string
	 */
	public function exportCode()
	{
		$default_properties = $this->_property->getDeclaringClass()->getDefaultProperties();

		$modifiers = \Reflection::getModifierNames( $this->_property->getModifiers() );

		$default_value = null;
		if ( array_key_exists( $this->_property->getName(), $default_properties ) )
		{
			$default_value = $default_properties[ $this->_property->getName() ];
			if ( !is_numeric( $default_value ) )
			{
				$default_value = "'{$default_value}'";
			}
		}

		return sprintf(
			'%s $%s%s;',
			join( ' ', $modifiers ),
			$this->_property->getName(),
			!is_null( $default_value ) ? " = {$default_value}" : ''
		);
	}
}
 