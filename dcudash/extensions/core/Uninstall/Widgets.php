<?php
/**
 * @brief		Uninstall callback
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Uninstall callback
 */
class _Widgets
{
	/**
	 * Code to execute before the application has been uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	array
	 */
	public function preUninstall( $application )
	{
	}

	/**
	 * Code to execute after the application has been uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	array
	 */
	public function postUninstall( $application )
	{
	}

	/**
	 * Code to execute when other applications are uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	void
	 * @deprecated	This is here for backwards-compatibility - all new code should go in onOtherUninstall
	 */
	public function onOtherAppUninstall( $application )
	{
		return $this->onOtherUninstall( $application );
	}

	/**
	 * Code to execute when other applications or plugins are uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @param	int		$plugin			Plugin ID
	 * @return	void
	 */
	public function onOtherUninstall( $application=NULL, $plugin=NULL )
	{
		/* clean up widget areas table */
		foreach ( \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas' ) as $row )
		{
			$data = json_decode( $row['area_widgets'], true );
			$deleted = false;
			foreach ( $data as $key => $widget )
			{
				if( $application !== NULL )
				{
					if ( isset( $widget['app'] ) and $widget['app'] == $application )
					{
						$deleted = true;
						unset( $data[$key] );
					}
				}

				if( $plugin !== NULL )
				{
					if ( isset( $widget['plugin'] ) and $widget['plugin'] == $plugin )
					{
						$deleted = true;
						unset( $data[$key] );
					}
				}
			}
			
			if ( $deleted === true )
			{
				\IPS\Db::i()->update( 'dcudash_dash_widget_areas', array( 'area_widgets' => json_encode( $data ) ), array( 'area_dash_id=? AND area_area=?', $row['area_dash_id'], $row['area_area'] ) );
			}
		}
	}
}