<?php
/**
 * @brief		Dashboard Widgets
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dashboard Widgets
 */
class _Widget extends \IPS\Widget
{
	/**
	 * Fetch the configuration for this unqiue ID. Looks in active tables and trash. When a widget is moved, saveOrder is called twice,
	 * once to remove the widget from column A and again to add it to column B. We store the widget removed from column A into the trash
	 * table.
	 *
	 * @param   string  $uniqueId   Widget's unique ID
	 * @return  array
	 */
	public static function getConfiguration( $uniqueId )
	{
		foreach( \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas' ) as $item )
		{
			$widgets = json_decode( $item['area_widgets'], TRUE );

			if( is_array( $widgets ) )
			{
				foreach( $widgets as $widget )
				{
					if ( $widget['unique'] == $uniqueId )
					{
						if ( isset( $widget['configuration'] ) )
						{
							return $widget['configuration'];
						}
					}
				}
			}
		}

		/* Still here? rummage in the trash */
		return parent::getConfiguration( $uniqueId );
	}

	/**
	 * Delete caches. We need a different name from the parent class otherwise the Dashes app hook will get stuck in infinite recursion
	 *
	 * @param	String	$key				Widget key
	 * @param	String	$app				Parent application
	 * @param	String	$plugin				Parent plugin
	 * @return	void
	 */
	static public function deleteCachesForBlocks( $key=NULL, $app=NULL, $plugin=NULL )
	{
		/* Delete any custom block caches relevant to this plug in */
		if ( $key OR $app )
		{
			$where = array( array( 'block_type=?', 'plugin' ) );

			if( $key )
			{
				$where[] = array( 'block_key=?', (string) $key );
			}

			if( $app )
			{
				$where[] = array( 'block_plugin_app=?', (string) $app );
			}

			$blocks = array();
			foreach( \IPS\Db::i()->select( '*', 'dcudash_blocks', $where ) as $row )
			{
				$blocks[ $row['block_key'] ] = $row;
			}

			if ( count( $blocks ) )
			{
				$uniqueIds = array();
				foreach( \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas' ) as $item )
				{
					$widgets = json_decode( $item['area_widgets'], TRUE );

					foreach( $widgets as $widget )
					{
						if ( $widget['app'] === 'dcudash' and $widget['key'] === 'Blocks' and isset( $widget['unique'] ) and isset( $widget['configuration'] ) and isset( $widget['configuration']['dcudash_widget_custom_block'] ) )
						{
							if ( in_array( $widget['configuration']['dcudash_widget_custom_block'], array_keys( $blocks ) ) )
							{
								$uniqueIds[] = $widget['unique'];
							}
						}
					}
				}

				foreach( \IPS\Db::i()->select( '*', 'core_widget_areas' ) as $item )
				{
					$widgets = json_decode( $item['widgets'], TRUE );

					foreach( $widgets as $widget )
					{
						if ( $widget['app'] === 'dcudash' and $widget['key'] === 'Blocks' and isset( $widget['unique'] ) and isset( $widget['configuration'] ) and isset( $widget['configuration']['dcudash_widget_custom_block'] ) )
						{
							if ( in_array( $widget['configuration']['dcudash_widget_custom_block'], array_keys( $blocks ) ) )
							{
								$uniqueIds[] = $widget['unique'];
							}
						}
					}
				}

				if ( count( $uniqueIds ) )
				{
					$widgetRow = \IPS\Db::i()->select( '*', 'core_widgets', array( '`key`=? and app=?', 'Blocks', 'dcudash' ) )->first();

					if ( ! empty( $widgetRow['caches'] ) )
					{
						$caches = json_decode( $widgetRow['caches'], TRUE );

						if ( is_array( $caches ) )
						{
							$save  = $caches;
							foreach( $caches as $key => $time )
							{
								foreach( $uniqueIds as $id )
								{
									if ( mb_stristr( $key, 'widget_Blocks_' . $id ) )
									{
										if ( isset( \IPS\Data\Store::i()->$key ) )
										{
											unset( \IPS\Data\Store::i()->$key );
										}

										unset( $save[ $key ] );
									}
								}
							}

							if ( count( $save ) !== count( $caches ) )
							{
								\IPS\Db::i()->update( 'core_widgets', array( 'caches' => ( count( $save ) ? json_encode( $save ) : NULL ) ), array( 'id=?', $widgetRow['id'] ) );
								unset( \IPS\Data\Store::i()->widgets );
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Return unique IDs in use
	 *
	 * @return array
	 */
	public static function getUniqueIds()
	{
		$uniqueIds = parent::getUniqueIds();
		foreach ( \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas' ) as $row )
		{
			$data = json_decode( $row['area_widgets'], TRUE );
			
			if ( count( $data ) )
			{
				foreach( $data as $widget )
				{
					if ( isset( $widget['unique'] ) )
					{ 
						$uniqueIds[] = $widget['unique'];
					}
				}
			}
		}
		
		return $uniqueIds;
	}
}
