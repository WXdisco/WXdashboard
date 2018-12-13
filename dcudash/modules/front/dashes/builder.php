<?php
/**
 * @brief		[Front] Dash Controller
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\modules\front\dashes;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * dash
 */
class _builder extends \IPS\core\modules\front\system\widgets
{
	/**
	 * Preview a block (from the ACP or elsewhere dynamically)
	 *
	 * @return html
	 */
	public function previewBlock()
	{
		$output = "";
		
		if ( isset( \IPS\Request::i()->block_plugin ) )
		{
			$block  = new \IPS\dcudash\Blocks\Block;
			$block->type       = "plugin";
			$block->plugin     = \IPS\Request::i()->block_plugin;
			$block->plugin_app = ( isset( \IPS\Request::i()->block_plugin_app ) ) ? \IPS\Request::i()->block_plugin_app : \IPS\Request::i()->block_app;
			$block->plugin_plugin = \IPS\Request::i()->block_plugin_plugin;
			$block->key		   = md5( mt_rand() );
			
			$params		= array();
			$block->content = NULL;

			if ( isset( \IPS\Request::i()->_sending ) )
			{
				foreach( explode( ",", \IPS\Request::i()->_sending ) as $field )
				{
					/* Multi-Selects will pass their parameters through as an array, so we need to make sure we check those properly to include all options. */
					if ( mb_strstr( $field, '[]' ) !== FALSE )
					{
						$field		= str_replace( '[]', '', $field );
						$isArray	= TRUE;
					}
					else
					{
						$isArray	= FALSE;
					}
					
					if ( $field and isset( \IPS\Request::i()->$field ) )
					{
						if ( $field == 'block_content' )
						{
							$block->content = \IPS\Request::i()->$field;
							
							if ( isset( \IPS\Request::i()->template_params ) )
							{
								$block->template_params = \IPS\Request::i()->template_params;
							}
							continue;
						}

						if( mb_strpos( $field, "widget_feed_container_" ) !== FALSE )
						{
							/* On means that the all checkbox is ticked */
							$params[ 'widget_feed_container'] = \IPS\Request::i()->$field == 'on' ? 0 : \IPS\Request::i()->$field;
							continue;
						}

						/* We need to handle tags special */
						if( $field == 'widget_feed_tags' )
						{
							$params[ $field ] = explode( ',', \IPS\Request::i()->$field );
							continue;
						}
						
						/* Is it an array? */
						if ( $isArray )
						{
							foreach( \IPS\Request::i()->$field AS $multi )
							{
								$params[ $field ][] = $multi;
							}
							continue;
						}

						$params[ $field ] = \IPS\Request::i()->$field;
					}
				}
			}
			
			$block->plugin_config = json_encode( $params );
			
			/* Template stuffs */
			if ( \IPS\Request::i()->block_template_use_how == 'copy' )
			{
				$block->widget()->template( array( $block, 'getTemplate' ) );
			}

			$output = $block->widget()->render();
		}
							
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
	}
	
	/**
	 * Get Output For Adding A New Block
	 *
	 * @return	void
	 */
	protected function getBlock()
	{		
		$key = $block = explode( "_", \IPS\Request::i()->blockID );
		
		if ( isset( \IPS\Request::i()->dashID ) )
		{
			try
			{
				foreach ( \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas', array( 'area_dash_id=?', \IPS\Request::i()->dashID ) ) as $item )
				{
					$blocks = json_decode( $item['area_widgets'], TRUE );
					
					foreach( $blocks as $block )
					{
						if( $block['key'] == $key[2] AND $block['unique'] == $key[3] )
						{ 
							if ( isset( $block['app'] ) and $block['app'] == $key[1] )
							{
								$widget = \IPS\Widget::load( \IPS\Application::load( $block['app'] ), $block['key'], $block['unique'], $block['configuration'], null, \IPS\Request::i()->orientation );
							}
							elseif ( isset( $block['plugin'] ) and $block['plugin'] == $key[1] )
							{
								$widget = \IPS\Widget::load( \IPS\Plugin::load( $block['plugin'] ), $block['key'], $block['unique'], $block['configuration'], null, \IPS\Request::i()->orientation );
							}
						}
					}
				}
			}
			catch ( \UnderflowException $e ) { }

			/* Make sure the current dash is set so the widgets have database/dash scope */
			\IPS\dcudash\Dashes\Dash::$currentDash = \IPS\dcudash\Dashes\Dash::load( \IPS\Request::i()->dashID );

			/* Have we got a database for this dash? */
			$database = \IPS\dcudash\Dashes\Dash::$currentDash->getDatabase();

			if ( $database )
			{
				\IPS\dcudash\Databases\Dispatcher::i()->setDatabase( $database->id );
			}
		}
		
		if ( !isset( $widget ) )
		{
			try
			{
				$widget = \IPS\Widget::load( \IPS\Application::load( $key[1] ), $key[2], $key[3], array(), null, \IPS\Request::i()->orientation );

			}
			catch ( \OutOfRangeException $e )
			{
				$widget = \IPS\Widget::load( \IPS\Plugin::load( $key[1] ), $key[2], $key[3], array(), null, \IPS\Request::i()->orientation );
			}
		}

		$output = (string) $widget;

		\IPS\Output::i()->output = ( $output ) ? $output :  \IPS\Theme::i()->getTemplate( 'widgets', 'core', 'front' )->blankWidget( $widget );
	}

	/**
	 * Get Configuration
	 *
	 * @return	void
	 */
	protected function getConfiguration()
	{
		/* Standard widget area, allow the core stuff to handle this */
		if( in_array( \IPS\Request::i()->area, array( 'sidebar', 'header', 'footer' ) ) )
		{
			return parent::getConfiguration();
		}
		
		$key	= explode( "_", \IPS\Request::i()->block );
		$blocks	= array( 'area_widgets' => NULL );
		
		/* Dashboard only stuff */
		try
		{
			$blocks       = \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas', array( 'area_dash_id=? AND area_area=?', \IPS\Request::i()->dashID, \IPS\Request::i()->dashArea ) )->first();

			$where = ( $key[0] ) == 'app' ? '`key`=? AND `app`=?' : '`key`=? AND `plugin`=?';
			$widgetMaster = \IPS\Db::i()->select( '*', 'core_widgets', array( $where, $key[2], $key[1] ) )->first();
		}
		catch ( \UnderflowException $e )
		{
		}
		
		$blocks	= json_decode( $blocks['area_widgets'], TRUE );
		$widget	= NULL;

		if( !empty( $blocks ) )
		{
			foreach ( $blocks as $k => $block )
			{
				if ( $block['key'] == $key[2] AND $block['unique'] == $key[3] )
				{
					if ( isset( $block['app'] ) and $block['app'] == $key[1] )
					{
						$widget = \IPS\Widget::load( \IPS\Application::load( $block['app'] ), $block['key'], $block['unique'], $block['configuration'] );
						$widget->menuStyle = $widgetMaster['menu_style'];
					}
					elseif ( isset( $block['plugin'] ) and $block['plugin'] == $key[1] )
					{
						$widget = \IPS\Widget::load( \IPS\Plugin::load( $block['plugin'] ), $block['key'], $block['unique'], $block['configuration'] );
						$widget->menuStyle = $widgetMaster['menu_style'];
					}
				}

				if( $widget !== NULL AND method_exists( $widget, 'configuration' ) )
				{
					$form = new \IPS\Helpers\Form( 'form', 'saveSettings' );
					if ( $widget->configuration( $form ) !== NULL )
					{
						if ( $values = $form->values() )
						{
							if ( method_exists( $widget, 'preConfig' ) )
							{
								$values = $widget->preConfig( $values );
							}
							
							$blocks[ $k ]['configuration'] = $values;
							\IPS\Db::i()->insert( 'dcudash_dash_widget_areas', array( 'area_dash_id' => \IPS\Request::i()->dashID, 'area_area' => \IPS\Request::i()->dashArea, 'area_widgets' => json_encode( $blocks ) ), TRUE );
							\IPS\Output::i()->json( 'OK' );
						}
						\IPS\Output::i()->output = $widget->configuration()->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'widgets', 'core' ) ), 'formTemplate' ), $widget );
					}
				}
			}
		}
	}
	
	/**
	 * Reorder Blocks
	 *
	 * @return	void
	 */
	protected function saveOrder()
	{
		$newOrder = array();
		$seen     = array();

		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$currentConfig = \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas', array( 'area_dash_id=? AND area_area=?', \IPS\Request::i()->dashID, \IPS\Request::i()->area ) )->first();
			$widgets = json_decode( $currentConfig['area_widgets'], TRUE );
		}
		catch ( \UnderflowException $e )
		{
			$widgets = array();
		}

		/* Loop over the new order and merge in current blocks so we don't lose config */
		if ( isset ( \IPS\Request::i()->order ) )
		{
			foreach ( \IPS\Request::i()->order as $block )
			{
				$block = explode( "_", $block );
				
				$added = FALSE;
				foreach( $widgets as $widget )
				{
					if ( $widget['key'] == $block[2] and $widget['unique'] == $block[3] )
					{
						$seen[]     = $widget['unique'];
						$newOrder[] = $widget;
						$added = TRUE;
						break;
					}
				}
				if( !$added )
				{
					$newBlock = array();
					
					if ( $block[0] == 'app' )
					{
						$newBlock['app'] = $block[1];
					}
					else
					{
						$newBlock['plugin'] = $block[1];
					}
					
					$newBlock['key'] 		  = $block[2];
					$newBlock['unique']		  = $block[3];
					$newBlock['configuration']	= array();

					/* Make sure this widget doesn't have configuration in another area */
					$newBlock['configuration'] = \IPS\dcudash\Widget::getConfiguration( $newBlock['unique'] );

					$seen[]     = $block[3];
					$newOrder[] = $newBlock;
				}
			}
		}

		/* Anything to update? */
		if ( count( $widgets ) > count( $newOrder ) )
		{
			/* No items left in area, or one has been removed */
			foreach( $widgets as $widget )
			{
				/* If we haven't seen this widget, it's been removed, so add to trash */
				if ( ! in_array( $widget['unique'], $seen ) )
				{
					\IPS\Widget::trash( $widget['unique'], $widget );
				}
			}
		}
		
		/* Check core_widget_areas to ensure that the block wasn't added there */
		if ( isset( \IPS\Request::i()->exclude ) and ! empty( \IPS\Request::i()->exclude ) )
		{
			$bits = explode( "_", \IPS\Request::i()->exclude );
			$this->_checkAndDeleteFromCoreWidgets( $bits[3], $seen );
		}
		
		/* Expire Caches so up to date information displays */
		\IPS\Widget::deleteCaches();

		/* Save to database */
		$orientation = ( isset( \IPS\Request::i()->orientation ) and \IPS\Request::i()->orientation === 'vertical' ) ? 'vertical' : 'horizontal';
		\IPS\Db::i()->replace( 'dcudash_dash_widget_areas', array( 'area_orientation' => $orientation, 'area_dash_id' => \IPS\Request::i()->dashID, 'area_widgets' => json_encode( $newOrder ), 'area_area' => \IPS\Request::i()->area ) );
		
		\IPS\dcudash\Dashes\Dash::load( \IPS\Request::i()->dashID )->postWidgetOrderSave();
	}
	
	/**
	 * Sometimes the widgets end up in the core table. We haven't really found out why this happens. It happens very rarely.
	 * It may be that the Dashboard JS mixin doesn't load so the core ajax URLs are used (system/widgets.php) and not the dcudash widget (dash/builder.php).
	 * This method ensures that any widgets in the core table are removed
	 *
	 * @param	string	$uniqueId	The unique key of the widget (eg: wzsj1233)
	 * @param	array	$widgets	Current widgets (eg from core_widget_areas.widgets (json decoded))
	 * @return	bool				True if something removed, false if not
	 */
	protected function _checkAndDeleteFromCoreWidgets( $uniqueId, $widgets )
	{
		if ( ! in_array( $uniqueId, $widgets ) )
		{
			/* This widget hasn't been seen, so it isn't in the dcudash table */
			try
			{
				$dcudashWidget = \IPS\Db::i()->select( '*', 'core_widget_areas', array( 'app=? and module=? and controller=? and area=?', 'dcudash', 'dashes', 'dash', \IPS\Request::i()->area ) )->first();
				$dcudashWidgets = json_decode( $dcudashWidget['widgets'], TRUE );
				$newWidgets = array();
				
				foreach( $dcudashWidgets as $item )
				{
					if ( $item['unique'] !== $uniqueId )
					{
						$newWidgets[] = $item;
					}
				}
				
				/* Anything to save? */
				if ( count( $newWidgets ) )
				{
					\IPS\Db::i()->replace( 'core_widget_areas', array( 'app' => 'dcudash', 'module' => 'dashes', 'controller' => 'dash', 'widgets' => json_encode( $newWidgets ), 'area' => \IPS\Request::i()->area ) );
				}
				else
				{
					/* Just remove the entire row */
					\IPS\Db::i()->delete( 'core_widget_areas', array( 'app=? and module=? and controller=? and area=?', 'dcudash', 'dashes', 'dash', \IPS\Request::i()->area ) );
				}
				
				return TRUE;
			}
			catch( \UnderFlowException $ex )
			{
				/* Well, it isn't there either... */
				return FALSE;
			}
		}
	}
}