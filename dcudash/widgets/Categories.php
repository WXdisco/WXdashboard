<?php
/**
 * @brief		Categories Widget
 * @package		DCU Dashboard
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		(c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Categories Widget
 */
class _Categories extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Categories';
	
	/**
	 * @brief	App
	 */
	public $app = 'dcudash';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		/* If we're not on a Dashboard dash, return nothing */
		if( !\IPS\dcudash\Dashes\Dash::$currentDash )
		{
			return '';
		}

		/* Scope makes it possible for this block to fire before the main block which sets up the dispatcher */
		$db = NULL;
		if ( ! \IPS\dcudash\Databases\Dispatcher::i()->databaseId )
		{
			try
			{
				$db = \IPS\dcudash\Dashes\Dash::$currentDash->getDatabase()->id;
			}
			catch( \Exception $ex )
			{

			}
		}
		else
		{
			$db = \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
		}

		if ( ! \IPS\dcudash\Dashes\Dash::$currentDash->full_path or ! $db )
		{
			return '';
		}

		$url = \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dcudash&path=" . \IPS\dcudash\Dashes\Dash::$currentDash->full_path, 'front', 'content_dcudash_path', \IPS\dcudash\Dashes\Dash::$currentDash->full_path );

		return $this->output($url);
	}
}