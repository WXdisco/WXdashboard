<?php
/**
 * @brief		Dash Model
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\Dashes;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Dash Model
 */
class _Router extends \IPS\Patterns\ActiveRecord
{
	/**
	 * Load Dashes Thing based on a URL.
	 * The URL is sometimes complex to figure out, so this will help
	 *
	 * @param	\IPS\Http\Url	$url	URL to load from
	 * @return	\IPS\dcudash\Dashes\Dash
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function loadFromUrl( \IPS\Http\Url $url )
	{
		if ( ! isset( $url->queryString['path'] ) )
		{
			throw new \OutOfRangeException();
		}
		
		$path = $url->queryString['path'];
		
		/* First, we need a dash */
		$dash = \IPS\dcudash\Dashes\Dash::loadFromPath( $path );
		
		/* What do we have left? */
		$whatsLeft = trim( preg_replace( '#' . $dash->full_path . '#', '', $path, 1 ), '/' );
		
		if ( $whatsLeft )
		{
			/* Check databases */
			$databases = iterator_to_array( \IPS\Db::i()->select( '*', 'dcudash_databases', array( 'database_dash_id > 0' ) ) );
			foreach( $databases as $db )
			{
				$classToTry = 'IPS\dcudash\Records' . $db['database_id'];
				try
				{
					$record = $classToTry::loadFromSlug( $whatsLeft, FALSE, FALSE );
					
					return $record;
				}
				catch( \Exception $ex ) { }
			}
			
			/* Check categories */
			foreach( $databases as $db )
			{
				$classToTry = 'IPS\dcudash\Categories' . $db['database_id'];
				try
				{
					$category = $classToTry::loadFromPath( $whatsLeft );
					
					if ( $category !== NULL )
					{
						return $category;
					}
				}
				catch( \Exception $ex ) { }
			}
		}
		else
		{
			/* It's a dash */
			return $dash;
		}
		
		/* No idea, sorry */
		throw new \InvalidArgumentException;
	}
}