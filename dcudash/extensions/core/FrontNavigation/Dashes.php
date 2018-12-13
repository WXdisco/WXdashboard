<?php
/**
 * @brief		Front Navigation Extension: Dashes
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Dashes
 */
class _Dashes extends \IPS\core\FrontNavigation\FrontNavigationAbstract
{
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('menu_content_dash');
	}
	
	/**
	 * Allow multiple instances?
	 *
	 * @return	string
	 */
	public static function allowMultiple()
	{
		return TRUE;
	}
	
	/**
	 * Get configuration fields
	 *
	 * @param	array	$configuration	The existing configuration, if editing an existing item
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function configuration( $existingConfiguration, $id = NULL )
	{
		$dashes = array();
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'dcudash_dashes' ), 'IPS\dcudash\Dashes\Dash' ) as $dash )
		{
			$dashes[ $dash->id ] = $dash->full_path;
		}
		
		return array(
			new \IPS\Helpers\Form\Select( 'menu_content_dash', isset( $existingConfiguration['menu_content_dash'] ) ? $existingConfiguration['menu_content_dash'] : NULL, NULL, array( 'options' => $dashes ), NULL, NULL, NULL, 'menu_content_dash' ),
			new \IPS\Helpers\Form\Radio( 'menu_title_dash_type', isset( $existingConfiguration['menu_title_dash_type'] ) ? $existingConfiguration['menu_title_dash_type'] : 0, NULL, array( 'options' => array( 0 => 'menu_title_dash_inherit', 1 => 'menu_title_dash_custom' ), 'toggles' => array( 1 => array( 'menu_title_dash' ) ) ), NULL, NULL, NULL, 'menu_title_dash_type' ),
			new \IPS\Helpers\Form\Translatable( 'menu_title_dash', NULL, NULL, array( 'app' => 'dcudash', 'key' => $id ? "dcudash_menu_title_{$id}" : NULL ), NULL, NULL, NULL, 'menu_title_dash' ),
		);
	}
	
	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @return	array
	 */
	public static function parseConfiguration( $configuration, $id )
	{
		if ( $configuration['menu_title_dash_type'] )
		{
			\IPS\Lang::saveCustom( 'dcudash', "dcudash_menu_title_{$id}", $configuration['menu_title_dash'] );
		}
		else
		{
			\IPS\Lang::deleteCustom( 'dcudash', "dcudash_menu_title_{$id}" );
		}
		
		unset( $configuration['menu_title_dash'] );
		
		return $configuration;
	}
		
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		if ( $this->permissions )
		{
			if ( $this->permissions != '*' )
			{
				return \IPS\Member::loggedIn()->inGroup( explode( ',', $this->permissions ) );
			}
			
			return TRUE;
		}
		
		/* Inherit from dash */
		$store = \IPS\dcudash\Dashes\Dash::getDashUrlStore();

		if ( isset( $store[ $this->configuration['menu_content_dash'] ] ) )
		{
			if ( $store[ $this->configuration['menu_content_dash'] ]['perm'] != '*' )
			{
				return \IPS\Member::loggedIn()->inGroup( explode( ',', $store[ $this->configuration['menu_content_dash'] ]['perm'] ) );
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title()
	{
		if ( $this->configuration['menu_title_dash_type'] )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( "dcudash_menu_title_{$this->id}" );
		}
		else
		{
			return \IPS\Member::loggedIn()->language()->addToStack( "dcudash_dash_{$this->configuration['menu_content_dash']}" );
		}
	}
	
	/**
	 * Get Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function link()
	{
		$store = \IPS\dcudash\Dashes\Dash::getDashUrlStore();
		
		if ( isset( $store[ $this->configuration['menu_content_dash'] ] ) )
		{
			return $store[ $this->configuration['menu_content_dash'] ]['url'];
		}
		
		/* Fall back here */
		return \IPS\dcudash\Dashes\Dash::load( $this->configuration['menu_content_dash'] )->url();
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active()
	{
		return ( \IPS\dcudash\Dashes\Dash::$currentDash and \IPS\dcudash\Dashes\Dash::$currentDash->id == $this->configuration['menu_content_dash'] );
	}
	
	/**
	 * Children
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public function children( $noStore=FALSE )
	{
		return array();
	}
}