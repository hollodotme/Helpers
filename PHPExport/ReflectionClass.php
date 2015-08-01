<?php
/**
 * Export class for a class
 *
 * @author hollodotme
 */

namespace hollodotme\Helpers\PHPExport;

require_once __DIR__ . '/ReflectionConstant.php';
require_once __DIR__ . '/ReflectionProperty.php';
require_once __DIR__ . '/ReflectionMethod.php';
require_once __DIR__ . '/ReflectionParameter.php';

/**
 * Class ReflectionClass
 *
 * @package hollodotme\Helpers\PHPExport
 */
class ReflectionClass
{

	/**
	 * @var \ReflectionClass
	 */
	protected $_reflection_class;

	/**
	 * Constructor
	 *
	 * @param \ReflectionClass $reflection_class
	 */
	public function __construct( \ReflectionClass $reflection_class )
	{
		$this->_reflection_class = $reflection_class;
	}

	/**
	 * Exports the PHP code
	 *
	 * @return string
	 */
	public function exportCode()
	{
		$code_lines = array();

		$code_lines[] = '<?php';

		// Export the namespace
		if ( $this->_reflection_class->getNamespaceName() )
		{
			$code_lines[] = '';
			$code_lines[] = 'namespace ' . $this->_reflection_class->getNamespaceName() . ';';
			$code_lines[] = '';
		}

		// Export the class' signature
		$code_lines[] = sprintf(
			'%s%s%s %s%s%s',
			$this->_reflection_class->isAbstract() ? 'abstract ' : '',
			$this->_reflection_class->isFinal() ? 'final ' : '',
			$this->_reflection_class->isInterface()
				? 'interface'
				: ($this->_reflection_class->isTrait() ? 'trait' : 'class'),
			$this->getClassName(),
			$this->_getParentClassName() ? " extends {$this->_getParentClassName()}" : '',
			$this->_getInterfaceNames() ? (" implements " . join( ', ', $this->_getInterfaceNames() )) : ''
		);

		$code_lines[] = '{';
		$code_lines[] = '';

		// Export constants
		foreach ( $this->_reflection_class->getConstants() as $name => $value )
		{
			$reflection_constant = new ReflectionConstant( $name, $value );
			$code_lines[] = "\t" . $reflection_constant->exportCode();
			$code_lines[] = '';
		}

		// Export properties
		foreach ( $this->_reflection_class->getProperties() as $property )
		{
			$reflection_property = new ReflectionProperty( $property );
			$code_lines[] = "\t" . $reflection_property->exportCode();
			$code_lines[] = '';
		}

		// Export methods
		foreach ( $this->_reflection_class->getMethods() as $method )
		{
			$reflection_method = new ReflectionMethod( $method );
			$code_lines[] = "\t" . $reflection_method->exportCode();
			$code_lines[] = '';
		}

		$code_lines[] = '}';

		return join( "\n", $code_lines );
	}

	/**
	 * Exports the PHP code into a file at the given file path
	 *
	 * @param string $file_path File path
	 *
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	public function exportFile( $file_path )
	{
		$file_path = trim( $file_path );
		if ( empty($file_path) )
		{
			throw new \InvalidArgumentException( 'Empty file path given.' );
		}

		$dir = dirname( $file_path );
		if ( !file_exists( $dir ) )
		{
			if ( !@mkdir( $dir, 0755, true ) )
			{
				throw new \Exception( "Could not create directory: {$dir}" );
			}
		}

		$content = $this->exportCode();

		if ( empty($content) )
		{
			throw new \Exception( "Got no content from export for class {$this->_reflection_class->getName()}" );
		}

		$result = file_put_contents( $file_path, $content );

		if ( $result == false )
		{
			throw new \Exception( "Could not write file: {$file_path}" );
		}
	}

	/**
	 * Returns the class' name without namespace
	 *
	 * @return string
	 */
	public function getClassName()
	{
		$namespace = preg_quote( $this->_reflection_class->getNamespaceName(), '#' );
		$class_name = $this->_reflection_class->getName();

		return preg_replace( "#^{$namespace}\\\#", '', $class_name );
	}

	/**
	 * Returns the parent's class full qualified name
	 *
	 * @return string
	 */
	protected function _getParentClassName()
	{
		$class_name = '';
		$parent = $this->_reflection_class->getParentClass();
		if ( $parent instanceof \ReflectionClass )
		{
			$class_name = $parent->getName();
		}

		return $class_name;
	}

	/**
	 * Returns an array of all implemented interfaces
	 *
	 * @return array
	 */
	protected function _getInterfaceNames()
	{
		$interfaces = $this->_reflection_class->getInterfaceNames();
		$namespace = $this->_reflection_class->getNamespaceName();
		array_walk(
			$interfaces, function ( &$name ) use ( $namespace )
			{
				if ( !empty($namespace) && strpos( $name, '\\' ) === false )
				{
					$name = '\\' . $name;
				}
			}
		);

		return $interfaces;
	}
}
 