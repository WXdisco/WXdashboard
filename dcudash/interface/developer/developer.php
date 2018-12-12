<?php
/**
 * @brief		Dashboard Developer Block Gateway
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
require_once str_replace( 'applications/dcudash/interface/developer/developer.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\External::i();

if ( \IPS\IN_DEV !== true AND ! \IPS\Theme::designersModeEnabled() )
{
	exit();
}

/* The CSS is parsed by the theme engine, and the theme engine has plugins, and those plugins need to now which theme ID we're using */
if ( \IPS\Theme::designersModeEnabled() )
{
	\IPS\Session\Front::i();
}

if ( isset( \IPS\Request::i()->file ) )
{
	$realPath = realpath( \IPS\ROOT_PATH . '/themes/' . \IPS\Request::i()->file );
	$pathContainer = realpath(\IPS\ROOT_PATH . '/themes/' );

	if( $realPath === FALSE OR mb_substr( $realPath, 0, mb_strlen( $pathContainer ) ) !== $pathContainer )
	{
		\IPS\Output::i()->error( 'node_error', '3C171/8', 403, '' );
		exit;
	}

	$file = file_get_contents( \IPS\ROOT_PATH . '/themes/' . \IPS\Request::i()->file );
		
	\IPS\Output::i()->sendOutput( preg_replace( '#<ips:template.+?\n#', '', $file ), 200, ( mb_substr( \IPS\Request::i()->file, -4 ) === '.css' ) ? 'text/css' : 'text/javascript' );
}