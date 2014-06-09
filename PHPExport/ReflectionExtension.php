<?php
/**
 * Export class for an extension
 *
 * @author hollodotme
 */

namespace hollodotme\Helpers\PHPExport;

require_once __DIR__ . '/ReflectionClass.php';

/**
 * Class ReflectionExtension
 *
 * @package hollodotme\Helpers\PHPExport
 */
class ReflectionExtension
{

	/**
	 * @var \ReflectionExtension
	 */
	protected $_extension;

	/**
	 * Constructor
	 *
	 * @param \ReflectionExtension $extension
	 */
	public function __construct( \ReflectionExtension $extension )
	{
		$this->_extension = $extension;
	}

	/**
	 * Exports the PHP code
	 *
	 * @return string
	 */
	public function exportCode()
	{
		$code_lines = array();

		foreach ( $this->_extension->getClasses() as $class )
		{
			$reflection_class = new ReflectionClass( $class );
			$code_lines[] = $reflection_class->exportCode();
			$code_lines[] = '';
		}

		return join( "\n", $code_lines );
	}

	/**
	 * Exports all PHP files for this extension
	 *
	 * @param string $directory
	 * @param bool   $create_sub_directories
	 *
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	public function exportFiles( $directory, $create_sub_directories = true )
	{
		$dir = realpath( $directory );

		if ( empty($dir) || !file_exists( $dir ) )
		{
			throw new \InvalidArgumentException( "Directory does not exist: {$directory}" );
		}

		foreach ( $this->_extension->getClasses() as $class )
		{
			$reflection_class = new ReflectionClass( $class );
			$current_dir = $dir;

			if ( $create_sub_directories )
			{
				$namespaces = explode( '\\', $class->getNamespaceName() );
				array_shift( $namespaces );
				$sub_dirs = join( DIRECTORY_SEPARATOR, $namespaces );

				if ( !empty($sub_dirs) )
				{
					$current_dir = $dir . DIRECTORY_SEPARATOR . $sub_dirs;
					if ( !file_exists( $current_dir ) && !@mkdir( $current_dir, 0755, true ) )
					{
						throw new \Exception( 'Could not create sub directories: ' . $sub_dirs );
					}
				}
			}

			$filename = $reflection_class->getClassName() . '.php';
			$file_path = $current_dir . DIRECTORY_SEPARATOR . $filename;

			$result = file_put_contents( $file_path, $reflection_class->exportCode() );
			if ( $result === false )
			{
				throw new \Exception( 'Could not create file: ' . $file_path );
			}
		}
	}

	/**
	 * Exports a PHAR file for the entire extension
	 *
	 * @param string $phar_name
	 * @param string $output_dir
	 * @param bool   $create_sub_directories
	 *
	 * @throws \Exception
	 */
	public function exportPHAR( $phar_name, $output_dir = '.', $create_sub_directories = true )
	{
		ini_set( 'phar.readonly', 0 );
		$temp_dir = '/tmp' . DIRECTORY_SEPARATOR . 'PHPExport_' . rand( 1, 9999 );

		if ( !file_exists( $temp_dir ) && !@mkdir( $temp_dir ) )
		{
			throw new \Exception( "Could not create temp directory: {$temp_dir}" );
		}

		$this->exportFiles( $temp_dir, $create_sub_directories );

		$phar = new \Phar( $output_dir . DIRECTORY_SEPARATOR . $phar_name, 0, $phar_name );
		$phar->buildFromDirectory( $temp_dir, '#\\.php$#' );
		$result = $phar->setStub( $phar->createDefaultStub( 'cli/index.php', 'www/index.php' ) );

		if ( !$result )
		{
			throw new \Exception( 'Could not set stub for phar: ' . $phar_name );
		}
	}
}
 