<?php
/**
 * @brief		Designers Mode Extension
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\DesignersMode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Designers Mode Extension
 */
class _Dcudash
{
	/**
	 * Anything need building?
	 *
	 * @return bool
	 */
	public function toBuild()
	{
		/* Yeah.. not gonna even bother trying to match up timestamps and such like and so on etc and etcetera is that spelled right? */
		return TRUE;
	}
	
	/**
	 * Designer's mode on
	 *
	 * @return bool
	 */
	public function on( $data=NULL )
	{
		\IPS\dcudash\Theme\Advanced\Theme::export();
		\IPS\dcudash\Media::exportDesignersModeMedia();
		\IPS\dcudash\Dashes\Dash::exportDesignersMode();
		
		return TRUE;
	}
	
	/**
	 * Designer's mode off
	 *
	 * @return bool
	 */
	public function off( $data=NULL )
	{
		\IPS\dcudash\Theme\Advanced\Theme::import();
		\IPS\dcudash\Media::importDesignersModeMedia();
		\IPS\dcudash\Dashes\Dash::importDesignersMode();
		
		return TRUE;
	}
}