<?php

/**
 * @group API
 * @covers ApiFormatPhp
 */
class ApiFormatPhpTest extends ApiFormatTestBase {

	protected $printerName = 'php';

	public static function provideGeneralEncoding() {
		return array(
			// Basic types
			array( array( null ), 'a:1:{i:0;N;}' ),
			array( array( true ), 'a:1:{i:0;b:1;}' ),
			array( array( false ), 'a:1:{i:0;b:0;}' ),
			array( array( 42 ), 'a:1:{i:0;i:42;}' ),
			array( array( 42.5 ), 'a:1:{i:0;d:42.5;}' ),
			array( array( 1e42 ), 'a:1:{i:0;d:1.0E+42;}' ),
			array( array( 'foo' ), 'a:1:{i:0;s:3:"foo";}' ),
			array( array( 'fóo' ), 'a:1:{i:0;s:4:"fóo";}' ),

			// Arrays and objects
			array( array( array() ), 'a:1:{i:0;a:0:{}}' ),
			array( array( array( 1 ) ), 'a:1:{i:0;a:1:{i:0;i:1;}}' ),
			array( array( array( 'x' => 1 ) ), 'a:1:{i:0;a:1:{s:1:"x";i:1;}}' ),
			array( array( array( 2 => 1 ) ), 'a:1:{i:0;a:1:{i:2;i:1;}}' ),

			// Content
			array( array( '*' => 'foo' ), 'a:1:{s:1:"*";s:3:"foo";}' ),
		);
	}

	public function testCrossDomainMangling() {
		$config = new HashConfig( array( 'MangleFlashPolicy' => false ) );
		$context = new RequestContext;
		$context->setConfig( new MultiConfig( array(
			$config,
			$context->getConfig(),
		) ) );
		$main = new ApiMain( $context );
		$main->getResult()->addValue( null, null, '< Cross-Domain-Policy >' );

		if ( !function_exists( 'wfOutputHandler' ) ) {
			function wfOutputHandler( $s ) {
				return $s;
			}
		}

		$printer = $main->createPrinterByName( 'php' );
		ob_start( 'wfOutputHandler' );
		$printer->initPrinter();
		$printer->execute();
		$printer->closePrinter();
		$ret = ob_get_clean();
		$this->assertSame( 'a:1:{i:0;s:23:"< Cross-Domain-Policy >";}', $ret );

		$config->set( 'MangleFlashPolicy', true );
		$printer = $main->createPrinterByName( 'php' );
		ob_start( 'wfOutputHandler' );
		try {
			$printer->initPrinter();
			$printer->execute();
			$printer->closePrinter();
			ob_end_clean();
			$this->fail( 'Expected exception not thrown' );
		} catch ( UsageException $ex ) {
			ob_end_clean();
			$this->assertSame(
				'This response cannot be represented using format=php. See https://bugzilla.wikimedia.org/show_bug.cgi?id=66776',
				$ex->getMessage(),
				'Expected exception'
			);
		}
	}

}
