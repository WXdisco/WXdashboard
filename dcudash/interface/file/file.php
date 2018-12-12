<?php
/**
 * @brief		Dashboard Download Handler for custom record upload fields
 * @package		DCU Dashboard
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		(c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/dcudash/interface/file/file.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\External::i();

try
{
	/* Load member */
	$member = \IPS\Member::loggedIn();
	
	/* Set up autoloader for Dashboard */

	/* Init */
	$databaseId  = intval( \IPS\Request::i()->database );
	$database    = \IPS\dcudash\Databases::load( $databaseId );
	$recordId    = intval( \IPS\Request::i()->record );
	$fileName    = urldecode( \IPS\Request::i()->file );
	$recordClass = '\IPS\dcudash\Records' . $databaseId;
	
	try
	{
		$record = $recordClass::load( $recordId );
	}
	catch( \OutOfRangeException $ex )
	{
		\IPS\Output::i()->error( 'no_module_permission', '2T279/1', 403, '' );
	}
	
	if ( ! $record->canView() )
	{
		\IPS\Output::i()->error( 'no_module_permission', '2T279/2', 403, '' );
	}

	/* Get file and data */
	try
	{
		$file = \IPS\File::get( 'dcudash_Records', $fileName );
	}
	catch( \Exception $ex )
	{
		\IPS\Output::i()->error( 'no_module_permission', '2T279/3', 404, '' ); 
	}
		
	$headers = array_merge( \IPS\Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'attachment', $file->originalFilename ), "X-Content-Type-Options" => "nosniff" ) );
	
	/* Send headers and print file */
	\IPS\Output::i()->sendStatusCodeHeader( 200 );
	\IPS\Output::i()->sendHeader( "Content-type: " . \IPS\File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );

	foreach( $headers as $key => $header )
	{
		\IPS\Output::i()->sendHeader( $key . ': ' . $header );
	}
	\IPS\Output::i()->sendHeader( "Content-Length: " . $file->filesize() );

	$file->printFile();
	exit;
}
catch ( \UnderflowException $e )
{
	\IPS\Dispatcher\Front::i();
	\IPS\Output::i()->sendOutput( '', 404 );
}