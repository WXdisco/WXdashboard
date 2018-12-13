<?php
/**
 * @brief		Template Plugin - Dashes: Dash Url
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\OutputPlugins;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Content: Block
 */
class _Dashurl
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = FALSE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( $data, $options )
	{
		if ( is_numeric( $data ) )
		{
			try
			{
				$url = \IPS\dcudash\Dashes\Dash::load( $data )->url();
			}
			catch( \OutOfRageException $ex )
			{
				$url = NULL;
			}
		}
		else
		{
			$data = ltrim( $data );
			$url = \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=dash&path=' . $data, 'front', 'content_dash_path', array( $data ) );
		}
		
		return "'" . (string) $url . "'";
	}
}