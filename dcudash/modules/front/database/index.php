<?php
/**
 * @brief		[Database] Category List Controller
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		(c) 2019 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/devCU/DCU-Dashboard 
 * @subpackage		Dashboard Content
 * @base		IPS 4 CMS
 * @since		09 JAN 2019
 * @version		1.0.0
 */

namespace IPS\dcudash\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List
 */
class _index extends \IPS\dcudash\Databases\Controller
{

	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage()
	{
		/* If the Databases module is set as default we end up here, but not routed through the database dispatcher which means the
			database ID isn't set. In that case, just re-route back through the dashes controller which handles everything. */
		if( \IPS\dcudash\Databases\Dispatcher::i()->databaseId === NULL )
		{
			$dashes = new \IPS\dcudash\modules\front\dashes\dash;
			return $dashes->manage();
		}

		$database = \IPS\dcudash\Databases::load( \IPS\dcudash\Databases\Dispatcher::i()->databaseId );

		/* Not using categories? */
		if ( ! $database->use_categories AND $database->cat_index_type === 0 )
		{
			$controller = new \IPS\dcudash\modules\front\database\category( $this->url );
			return $controller->view();
		}
		
		$this->view();
	}

	/**
	 * Display database category list.
	 *
	 * @return	void
	 */
	protected function view()
	{
		$database    = \IPS\dcudash\Databases::load( \IPS\dcudash\Databases\Dispatcher::i()->databaseId );
		$recordClass = 'IPS\dcudash\Records' . \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
		$url         = \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dash&path=" . \IPS\dcudash\Dashes\Dash::$currentDash->full_path, 'front', 'content_dash_path', \IPS\dcudash\Dashes\Dash::$currentDash->full_path );

		/* RSS */
		if ( $database->rss )
		{
			/* Show the link */
			\IPS\Output::i()->rssFeeds[ $database->_title ] = $url->setQueryString( 'rss', 1 );

			/* Or actually show RSS feed */
			if ( isset( \IPS\Request::i()->rss ) )
			{
				$document     = \IPS\Xml\Rss::newDocument( $url, \IPS\Member::loggedIn()->language()->get('content_db_' . $database->id ), \IPS\Member::loggedIn()->language()->get('content_db_' . $database->id . '_desc' ) );
				$contentField = 'field_' . $database->field_content;
				
				foreach ( $recordClass::getItemsWithPermission( array(), $database->field_sort . ' ' . $database->field_direction, $database->rss, 'read' ) as $record )
				{
					$content = $record->$contentField;
						
					if ( $record->record_image )
					{
						$content = \IPS\dcudash\Theme::i()->getTemplate( 'listing', 'dcudash', 'database' )->rssItemWithImage( $content, $record->record_image );
					}

					$document->addItem( $record->_title, $record->url(), $content, \IPS\DateTime::ts( $record->_publishDate ), $record->_id );
				}
		
				/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
				\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
			}
		}

		$dash = isset( \IPS\Request::i()->dash ) ? intval( \IPS\Request::i()->dash ) : 1;

		if( $dash < 1 )
		{
			$dash = 1;
		}

		if ( $database->cat_index_type === 1 and ! isset( \IPS\Request::i()->show ) )
		{
			/* Featured */
			$limit = 0;
			$count = 0;

			if ( isset( \IPS\Request::i()->dash ) )
			{
				$limit = $database->featured_settings['perdash'] * ( $dash - 1 );
			}

			$where = ( $database->featured_settings['featured'] ) ? array( array( 'record_featured=?', 1 ) ) : NULL;
			
			if ( isset( $database->featured_settings['categories'] ) and is_array( $database->featured_settings['categories'] ) and count( $database->featured_settings['categories'] ) )
			{
				$categoryField = "`dcudash_custom_database_{$database->_id}`.`category_id`";
				$where[] = array( \IPS\Db::i()->in( $categoryField, array_values( $database->featured_settings['categories'] ) ) );
			}
			
			$dashboards = $recordClass::getItemsWithPermission( $where, 'record_pinned DESC, ' . $database->featured_settings['sort'] . ' ' . $database->featured_settings['direction'], array( $limit, $database->featured_settings['perdash'] ), 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE, FALSE, FALSE, FALSE );

			if ( $database->featured_settings['pagination'] )
			{
				$count = $recordClass::getItemsWithPermission( $where, 'record_pinned DESC, ' . $database->featured_settings['sort'] . ' ' . $database->featured_settings['direction'], $database->featured_settings['perdash'], 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );
			}

			/* Pagination */
			$pagination = array(
				'dash'  => $dash,
				'dashes' => ( $count > 0 ) ? ceil( $count / $database->featured_settings['perdash'] ) : 1
			);
			
			/* Make sure we are viewing a real dash */
			if ( $dash > $pagination['dashes'] )
			{
				\IPS\Output::i()->redirect( \IPS\Request::i()->url()->setQueryString( 'dash', 1 ), NULL, 303 );
			}
			
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'database_index/featured.css', 'dcudash', 'front' ) );
			\IPS\Output::i()->title = ( $dash > 1 ) ? \IPS\Member::loggedIn()->language()->addToStack( 'title_with_dash_number', FALSE, array( 'sprintf' => array( $database->dashTitle(), $dash ) ) ) : $database->dashTitle();

			\IPS\dcudash\Databases\Dispatcher::i()->output .= \IPS\Output::i()->output = \IPS\dcudash\Theme::i()->getTemplate( $database->template_featured, 'dcudash', 'database' )->index( $database, $dashboards, $url, $pagination );
		}
		else
		{
			/* Category view */
			$class = '\IPS\dcudash\Categories' . $database->id;
			
			/* Load into memory */
			$class::loadIntoMemory();
			$categories = $class::roots();

			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'records/index.css', 'dcudash', 'front' ) );
			\IPS\Output::i()->title = $database->dashTitle();
			\IPS\dcudash\Databases\Dispatcher::i()->output .= \IPS\Output::i()->output = \IPS\dcudash\Theme::i()->getTemplate( $database->template_categories, 'dcudash', 'database' )->index( $database, $categories, $url );
		}
	}

	/**
	 * Show the pre add record form. This is used when no category is set.
	 *
	 * @return	void
	 */
	protected function form()
	{
		/* If the dash is the default dash and Dashes is the default app, the node selector cannot find the dash as it bypasses the Database dispatcher */
		if ( \IPS\dcudash\Dashes\Dash::$currentDash === NULL and \IPS\dcudash\Databases\Dispatcher::i()->databaseId === NULL and isset( \IPS\Request::i()->dash_id ) )
		{
			try
			{
				\IPS\dcudash\Dashes\Dash::$currentDash = \IPS\dcudash\Dashes\Dash::load( \IPS\Request::i()->dash_id );
				$database = \IPS\dcudash\Dashes\Dash::$currentDash->getDatabase();
				
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_dash_404', '2T389/1', 404, '' );
			}
		}
		else if ( \IPS\dcudash\Dashes\Dash::$currentDash === NULL and \IPS\dcudash\Databases\Dispatcher::i()->databaseId === NULL and isset( \IPS\Request::i()->d ) )
		{
			\IPS\dcudash\Dashes\Dash::$currentDash = \IPS\dcudash\Dashes\Dash::loadByDatabaseId( \IPS\Request::i()->d );
		}
		
		$form = new \IPS\Helpers\Form( 'select_category', 'continue' );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\Node( 'category', NULL, TRUE, array(
			'url'					=> \IPS\dcudash\Dashes\Dash::$currentDash->url()->setQueryString( array( 'do' => 'form', 'dash_id' => \IPS\dcudash\Dashes\Dash::$currentDash->id ) ),
			'class'					=> 'IPS\dcudash\Categories' . \IPS\dcudash\Databases\Dispatcher::i()->databaseId,
			'permissionCheck'		=> function( $node )
			{
				if ( $node->can( 'view' ) )
				{
					if ( $node->can( 'add' ) )
					{
						return TRUE;
					}

					return FALSE;
				}

				return NULL;
			},
		) ) );

		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( $values['category']->url()->setQueryString( 'do', 'form' ) );
		}

		\IPS\Output::i()->title						= \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_select_category' );
		\IPS\Output::i()->breadcrumb[]				= array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_select_category' ) );
		\IPS\dcudash\Databases\Dispatcher::i()->output	= \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'records' )->categorySelector( $form );
	}
}