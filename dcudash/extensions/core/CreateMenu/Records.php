<?php
/**
 * @brief		Create Menu Extension : Records
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\CreateMenu;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Menu Extension: Records
 */
class _Records
{
	/**
	 * Get Items
	 *
	 * @return	array
	 */
	public function getItems()
	{
		$items = array();
		
		foreach( \IPS\dcudash\Databases::databases() as $database )
		{
			$theOnlyCategory = NULL;
			if ( $database->dash_id > 0 and $database->can('view') and $database->can('add') )
			{
				$catClass = '\IPS\dcudash\Categories' . $database->id;
				if ( $catClass::canOnAny('add') )
				{
					try
					{
						$dash = \IPS\dcudash\Dashes\Dash::load( $database->dash_id );

						if( $database->use_categories AND $theOnlyCategory = $catClass::theOnlyNode() )
						{
							$items[ 'dcudash_create_menu_records_' . $database->id ] = array(
								'link' 		=> $theOnlyCategory->url()->setQueryString( array( 'do' => 'form', 'd' => $database->id ) )
							);
							continue;
						}

						$items[ 'dcudash_create_menu_records_' . $database->id ] = array(
							'link' 			=> $dash->url()->setQueryString( array( 'do' => 'form', 'd' => $database->id ) ),
							'extraData'		=> ( $database->use_categories ) ? array( 'data-ipsDialog' => true, 'data-ipsDialog-size' => "narrow" ) : array(),
							'title' 		=> 'dcudash_select_category'
						);
						
					}
					catch( \OutOfRangeException $ex ) { }
				}
			}
		}
		
		ksort( $items );
		
		return $items;
	}
}