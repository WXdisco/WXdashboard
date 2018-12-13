<?php
/**
 * @brief		Template Plugin - Content: Database
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
 * Template Plugin - Content: Database
 */
class _Database
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = FALSE;
	
	/**
	 * @brief	Record how many database tags there are per dash
	 */
	public static $count = 0;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( $data, $options )
	{
		if ( isset( $options['category'] ) )
		{
			return '\IPS\dcudash\Databases\Dispatcher::i()->setDatabase( "' . $data . '" )->setCategory( "' . $options['category'] . '" )->run()';
		}
		
		return '\IPS\dcudash\Databases\Dispatcher::i()->setDatabase( "' . $data . '" )->run()';
	}
	
	/**
	 * Do any processing before a dash is added/saved
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options  Array of options
	 * @param	object		$dash	  Dash being edited/saved
	 * @return	void
	 */
	public static function preSaveProcess( $data, $options, $dash )
	{
		/* Keep a count of databases used so far */
		static::$count++;
		
		if ( static::$count > 1 )
		{
			throw new \LogicException( \IPS\Member::loggedIn()->language()->addToStack('dcudash_err_db_already_on_dash') );
		}
	}
	
	/**
	 * Do any processing after a dash is added/saved
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options  Array of options
	 * @param	object		$dash	  Dash being edited/saved
	 * @return	void
	 */
	public static function postSaveProcess( $data, $options, $dash )
	{
		$database = NULL;
		
		try
		{
			if ( is_numeric( $data ) )
			{
				$database = \IPS\dcudash\Databases::load( $data );
			}
			else
			{
				$database = \IPS\dcudash\Databases::load( $data, 'database_key' );
			}
			
			if ( $database->id AND $dash->id )
			{
				try
				{
					$dash->mapToDatabase( $database->id );
				}
				catch( \LogicException $ex )
				{
					throw new \LogicException( $ex->getMessage() );
				}
			}
		}
		catch( \OutofRangeException $ex ) { }
	}

}