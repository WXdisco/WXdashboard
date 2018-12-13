<?php
/**
 * @brief		Template Plugin - Dashes: Media
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
 * Template Plugin - Content: Media
 */
class _Media
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = TRUE;
	
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
				$url = \IPS\dcudash\Media::load( $data )->url();
			}
			catch( \OutOfRangeException $ex )
			{
				$url = NULL;
			}
		}
		else
		{
			try
			{
				$url = \IPS\dcudash\Media::load( $data, 'media_full_path' )->url();
			}
			catch( \OutOfRangeException $ex )
			{
				$url = NULL;
			}
		}
		
		return "'" . (string) $url . "'";
	}
}