<?php
/**
 * Export class for a constant
 * @author hollodotme
 */

namespace hollodotme\Helpers\PHPExport;

/**
 * Class ReflectionConstant
 *
 * @package hollodotme\Helpers\PHPExport
 */
class ReflectionConstant
{

	/**
	 * @var string
	 */
	protected $_name;

	/**
	 * @var float|int|null|string
	 */
	protected $_value;

	/**
	 * Construct
	 *
	 * @param string                $name  Name
	 * @param int|string|float|null $value Value
	 */
	public function __construct( $name, $value )
	{
		$this->_name = $name;
		$this->_value = $value;
	}

	/**
	 * Exports the PHP code
	 *
	 * @return string
	 */
	public function exportCode()
	{
		$value = $this->_value;
		if ( !is_null( $value ) && !is_numeric( $value ) )
		{
			$value = "'{$value}'";
		}

		return sprintf(
			'const %s%s;',
			$this->_name,
			!is_null( $value ) ? " = {$value}" : ''
		);
	}
}
 