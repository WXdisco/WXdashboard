<?php
/**
 * @brief		[Database] Category Controller
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * dash
 */
class _category extends \IPS\dcudash\Databases\Controller
{

	/**
	 * Store any active filters for this view
	 *
	 * @param	array
	 */	
	static public $activeFilters = array();
	
	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage()
	{
		$this->view();
	}

	/**
	 * Clear any filters
	 *
	 * @return void
	 */
	public function clearFilters()
	{
		\IPS\Session::i()->csrfCheck();
		
		$catClass = 'IPS\dcudash\Categories' .  \IPS\dcudash\Databases\Dispatcher::i()->databaseId;

		try
		{
			$category = $catClass::loadAndCheckPerms( \IPS\dcudash\Databases\Dispatcher::i()->categoryId );
			$category->saveFilterCookie( FALSE );

			\IPS\Output::i()->redirect( $category->url(), 'dcudash_filters_cleared' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2T254/1', 403, '' );
		}
	}

	/**
	 * Display a category. Please.
	 *
	 * @return	void
	 */
	public function view()
	{
		$category     = NULL;
		$fieldClass   = 'IPS\dcudash\Fields' .  \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
		$catClass     = 'IPS\dcudash\Categories' .  \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
		$database     = \IPS\dcudash\Databases::load( \IPS\dcudash\Databases\Dispatcher::i()->databaseId );
		$breadcrumbs  = NULL;

		try
		{
			$category = $catClass::loadAndCheckPerms( \IPS\dcudash\Databases\Dispatcher::i()->categoryId );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2T254/2', 403, '' );
		}
		
		$customFields = $fieldClass::data( 'view', $category, $fieldClass::FIELD_SKIP_TITLE_CONTENT );

		if ( ! $database->use_categories )
		{
			$breadcrumbs = \IPS\Output::i()->breadcrumb;
		}
		
		$RecordsClass = $category::$contentItemClass;
				
		/* Check cookie */
		$where = array();
		$cookie = $category->getFilterCookie();
		
		if ( $cookie !== NULL )
		{
			foreach( $cookie as $f => $v )
			{
				$k = 'content_field_' . $f;
				if ( isset( $customFields[ $f ] ) and !isset( \IPS\Request::i()->$k ) and $v !== '___any___' )
				{
					if ( is_array( $v ) )
					{
						if ( array_key_exists( 'start', $v ) or array_key_exists( 'end', $v ) )
						{
							$start = ( $v['start'] instanceof \IPS\DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
							$end   = ( $v['end'] instanceof \IPS\DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );
							
							if ( $start or $end )
							{
								$where[] = array( '( ' . mb_substr( $k, 8 ) . ' BETWEEN ' . $start . ' AND ' . $end . ' )' );
							}
						}
						else
						{
							$like = array();
							foreach( $v as $val )
							{
								if ( $val === 0 or ! empty( $val ) )
								{
									$like[]  = "CONCAT( ',', " .  mb_substr( $k, 8 ) . ", ',') LIKE '%," . \IPS\Db::i()->real_escape_string( $val ) . ",%'";
								}
							}
							
							$where[] = array( '( ' . \IPS\Db::i()->in( mb_substr( $k, 8 ), $v ) .  ( count( $like ) ? " OR (" . implode( ' OR ', $like ) . ') )' : ')' ) );
						}
					}
					else
					{
						if ( is_bool( $v ) )
						{
							/* YesNo fields are false or true */
							if ( $v === false )
							{
								$where[] = array( '(' . mb_substr( $k, 8 ) . ' IS NULL or ' . mb_substr( $k, 8 ) . '=0)' );
							}
							else
							{
								$where[] = array( mb_substr( $k, 8 ) . "=1" );
							}
						}
						else
						{
							if ( $v !== 0 and ! $v )
							{
								$where[] = array( mb_substr( $k, 8 ) . " IS NULL" );
							}
							else
							{
								$where[] = array( mb_substr( $k, 8 ) . "=?", $v );
							}
						}
					}
					
					static::$activeFilters[ $f ] = array( 'field' => $customFields[ $f ], 'value' => $customFields[ $f ]->displayValue( $v ) );
				}
			}
		}
		
		if ( ! count( $where ) )
		{
			$where = NULL;
		}
		
		$table = new \IPS\Helpers\Table\Content( 'IPS\dcudash\Records' . \IPS\dcudash\Databases\Dispatcher::i()->databaseId, $category->url(), $where, $category, NULL, 'read', isset( \IPS\Request::i()->rss ) ? FALSE : TRUE );
		$table->tableTemplate = array( \IPS\dcudash\Theme::i()->getTemplate( $category->_template_listing, 'dcudash', 'database' ), 'categoryTable' );
		$table->rowsTemplate = array( \IPS\dcudash\Theme::i()->getTemplate( $category->_template_listing, 'dcudash', 'database' ), 'recordRow' );
		$table->baseUrl = $table->baseUrl->setQueryString( 'd', \IPS\dcudash\Databases\Dispatcher::i()->databaseId );
		$table->hover = TRUE;
		$table->sortBy		  = ( isset( \IPS\Request::i()->sortby ) ) ? \IPS\Request::i()->sortby  : (  $database->field_sort ?  $RecordsClass::$databaseTable . '.' . $RecordsClass::$databasePrefix . $database->field_sort : 'record_last_comment' );
		$table->sortDirection = ( isset( \IPS\Request::i()->sortdirection ) ) ? \IPS\Request::i()->sortdirection : ( $database->field_direction ? $database->field_direction : 'desc' );
		$table->limit		  = $database->field_perdash   ? $database->field_perdash   : 25;
		$table->title = \IPS\Member::loggedIn()->language()->addToStack( $database->use_categories ? 'x_records_in_this_category' : 'x_records' , FALSE, array( 'sprintf' => array( $RecordsClass::contentCount( $category, FALSE ), $database->recordWord( $RecordsClass::contentCount( $category, FALSE ) ) ) ) );

		/* Set up sort fields to allow sorting numerically or by date */
		$sortFields = $customFields;
		$sortFields[ $database->field_title ] = $fieldClass::load( $database->field_title );
		foreach( $sortFields as $id => $obj )
		{
			if ( $table->sortBy == $RecordsClass::$databaseTable . '.' . 'field_' . $id OR $table->sortBy == 'field_' . $id )
			{
				if ( $obj->type == 'Number' )
				{
					$table->sortOptions[ $table->sortBy ] = 'LENGTH(`field_' . $id . '`) ' . $table->sortDirection . ',`field_' . $id . '`';
					break;
				}
				elseif( $obj->type == 'Date' )
				{
					$table->sortOptions[ $table->sortBy ] = 'CAST(`field_' . $id . '` AS UNSIGNED)';
					break;
				}
			}
		}
		
		/* Make sure table doesn't add breadcrumbs if we're not using categories */
		if ( ! $database->use_categories )
		{
			\IPS\Output::i()->breadcrumb = $breadcrumbs;
		}

		/* Custom Search */
		$filterOptions = array(
				'all'			=> 'content_all_records',
				'open'			=> 'content_open_records',
				'locked'		=> 'content_locked_records',
		);
		$timeFrameOptions = array(
				'show_all'			=> 'show_all',
				'today'				=> 'today',
				'last_5_days'		=> 'last_5_days',
				'last_7_days'		=> 'last_7_days',
				'last_10_days'		=> 'last_10_days',
				'last_15_days'		=> 'last_15_days',
				'last_20_days'		=> 'last_20_days',
				'last_25_days'		=> 'last_25_days',
				'last_30_days'		=> 'last_30_days',
				'last_60_days'		=> 'last_60_days',
				'last_90_days'		=> 'last_90_days',
		);

		if ( \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->last_visit)
		{
			$timeFrameOptions['since_last_visit'] = \IPS\Member::loggedIn()->language()->addToStack('since_last_visit', FALSE, array( 'sprintf' => array( \IPS\DateTime::ts( \IPS\Member::loggedIn()->last_visit ) ) ) );
		}

		$sortBy = array(
			'record_updated'	=> 'content_record_last_updated',
			'record_comments'		=> 'content_record_comments',
			'record_views'			=> 'content_record_views',
			'field_' . $database->field_title	=> 'content_record_title',
			'record_publish_date'	=> 'content_record_publish_date'
		);
		
		/* Ensure we have all sort options available */
		$table->sortOptions = array_unique( array_merge( $table->sortOptions, array_combine( array_keys( $sortBy ), array_keys( $sortBy ) ) ) );
		
		/* To avoid confusion, label 'updated' as 'Recently Updated' as last comment */
		\IPS\Member::loggedIn()->language()->words[ $table->langPrefix . 'sort_updated' ] = \IPS\Member::loggedIn()->language()->addToStack('content_record_last_comment');

		if ( !isset( $sortBy[ $database->field_sort ] ) )
		{
			switch ( $database->field_sort )
			{
				case 'primary_id_field':
					$sortBy[ $database->field_sort ] = 'database_field__id';
					$table->sortOptions['database_field__id'] = $database->field_sort;
					\IPS\Member::loggedIn()->language()->words['sort_database_field__id'] = \IPS\Member::loggedIn()->language()->addToStack('database_field__id');
					break;
				case 'member_id':
					$sortBy[ $database->field_sort ] = 'database_field__member';
					$table->sortOptions['database_field__member'] = $database->field_sort;
					\IPS\Member::loggedIn()->language()->words['sort_database_field__member'] = \IPS\Member::loggedIn()->language()->addToStack('database_field__member');
					break;
				case 'record_rating':
					$sortBy[ $database->field_sort ] = 'database_field__rating';
					$table->sortOptions['rating'] = $database->field_sort;
					\IPS\Member::loggedIn()->language()->words['sort_database_field__rating'] = \IPS\Member::loggedIn()->language()->addToStack('database_field__rating');
					break;
			}
		}
		
		if ( !$database->options['comments'] )
		{
			unset ( $sortBy['record_last_comment'] );
			unset ( $sortBy['record_comments'] );
			unset ( $table->sortOptions['record_last_comment'] );
			unset ( $table->sortOptions['record_comments'] );
			unset ( $table->sortOptions['last_comment'] );
			unset ( $table->sortOptions['num_comments'] );
		}

		if ( !$database->options['reviews'] )
		{
			unset ( $table->sortOptions['num_reviews'] );
			unset( $table->sortOptions['rating'] );
		}

		/* If the sort field isn't one of the above, best add it */
		if ( mb_substr( $database->field_sort, 0, 6 ) === 'field_' )
		{
			if ( $database->field_title !== mb_substr( $database->field_sort, 6 ) )
			{
				$sortBy[ $database->field_sort ] = \IPS\Member::loggedIn()->language()->addToStack( 'content_field_' . mb_substr( $database->field_sort, 6 ) );
				$table->sortOptions[ $database->field_sort ] = $database->field_sort;
			}
		}

		$table->advancedSearch = array(
			'record_type'	 => array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $filterOptions ) ),
			'sort_by'		 => array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $sortBy ) ),
			'sort_direction' => array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => array(
				'asc'			=> 'asc',
				'desc'			=> 'desc',
			) )
			),
			'time_frame'	=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $timeFrameOptions ) ),
		);

		foreach( $customFields as $obj )
		{
			if ( $obj->filter )
			{
				\IPS\Member::loggedIn()->language()->words['content_field_' . $obj->id ] = $obj->_title;
				if ( in_array( $obj->type, array( 'Date', 'DateRange' ) ) )
				{
					$table->advancedSearch[ 'content_field_' . $obj->id ] = array( \IPS\Helpers\Table\SEARCH_DATE_RANGE, array( 'noDefault' => true ) );
				}
				else if ( $obj->type == 'Number' )
				{
					$table->advancedSearch[ 'content_field_' . $obj->id ] = array( \IPS\Helpers\Table\SEARCH_NUMERIC_TEXT, array( 'noDefault' => true ) );
				}
				else if ( $obj->type == 'YesNo' )
				{
					$table->advancedSearch[ 'content_field_' . $obj->id ] = array( \IPS\Helpers\Table\SEARCH_BOOL, array( 'noDefault' => true ) );
				}
				else
				{
					$table->advancedSearch[ 'content_field_' . $obj->id ] = array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $obj->extra, 'multiple' => TRUE, 'noDefault' => true ) );
				}
				
				$table->advancedSearch['sort_by'][1]['options']['field_' . $obj->id ] = 'content_field_' . $obj->id;
			}

			if ( in_array( $obj->type, array( 'Date', 'DateRange' ) ) )
			{
				$table->sortOptions[ 'field_' . $obj->id ] = 'CAST(`field_' . $obj->id . '` AS UNSIGNED)';
			}
			else if ( $obj->type == 'Number' )
			{
				$table->sortOptions[ 'field_' . $obj->id ] = 'LENGTH(`field_' . $obj->id . '`) ' . $table->sortDirection . ',`field_' . $obj->id . '`';
			}
			else
			{
				$table->sortOptions[ 'field_' . $obj->id ] = isset( $table->sortOptions[ 'field_' . $obj->id ] ) ? $table->sortOptions[ 'field_' . $obj->id ] : 'field_' . $obj->id;
			}
		}

		$table->advancedSearchCallback = function( $table, $values ) use ( $database, $sortBy, $customFields )
		{
			/* Type */
			foreach( $values as $k => $v )
			{
				if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
				{
					if ( is_array( $v ) )
					{
						if ( array_key_exists( 'start', $v ) or array_key_exists( 'end', $v ) )
						{
							$start = ( $v['start'] instanceof \IPS\DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
							$end   = ( $v['end'] instanceof \IPS\DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );
							
							if ( $start or $end )
							{
								$table->where[] = array( '( ' . mb_substr( $k, 8 ) . ' BETWEEN ' . $start . ' AND ' . $end . ' )' );
							}
						}
						else
						{
							$like = array();
							foreach( $v as $val )
							{
								if ( $val === 0 or ! empty( $val ) )
								{
									$like[]  = "CONCAT( ',', " .  mb_substr( $k, 8 ) . ", ',') LIKE '%," . \IPS\Db::i()->real_escape_string( $val ) . ",%'";
								}
							}
							
							$table->where[] = array( '( ' . \IPS\Db::i()->in( mb_substr( $k, 8 ), $v ) . ( count( $like ) ? " OR (" . implode( ' OR ', $like ) . ') )' : ')' ) );
						}
					}
					else
					{
						if ( $v !== '___any___' )
						{ 
							if ( is_bool( $v ) )
							{
								/* YesNo fields are false or true */
								if ( $v === false )
								{
									$table->where[] = array( '(' . mb_substr( $k, 8 ) . ' IS NULL or ' . mb_substr( $k, 8 ) . '=0)' );
								}
								else
								{
									$table->where[] = array( mb_substr( $k, 8 ) . "=1" );
								}
							}
							else
							{
								if ( $v !== 0 and ! $v )
								{
									$table->where[] = array( mb_substr( $k, 8 ) . " IS NULL" );
								}
								else
								{
									$table->where[] = array( mb_substr( $k, 8 ) . "=?", $v );
								}
							}
						}
					}

					$key =  mb_substr( $k, 14 );
					\IPS\dcudash\modules\front\database\category::$activeFilters[ $key ] = array( 'field' => $customFields[ $key ], 'value' => $customFields[ $key ]->displayValue( $v ) );
				}
			}

			if ( isset( $values['record_type'] ) )
			{
				switch ( $values['record_type'] )
				{
					case 'open':
						$table->where[] = 'record_locked=0';
						break;
					case 'locked':
						$table->where[] = 'record_locked=1';
						break;
				}
			}

			/* Sort */
			if ( isset( $values['sort_by'] ) and isset( $sortBy[ $values['sort_by'] ] ) )
			{
				if ( isset( $customFields[ mb_substr( $values['sort_by'], 6 ) ] ) and $customFields[ mb_substr( $values['sort_by'], 6 ) ]->type == 'Number' )
				{
					$table->sortOptions[ $values['sort_by'] ] = 'LENGTH(`' . $values['sort_by'] . '`) ' . $table->sortDirection . ',`' . $values['sort_by'] . '`';
				}
				elseif( isset( $customFields[ mb_substr( $values['sort_by'], 6 ) ] ) and $customFields[ mb_substr( $values['sort_by'], 6 ) ]->type == 'Date' )
				{
					$table->sortOptions[ $values['sort_by'] ] = 'CAST(`' . $values['sort_by'] . '` AS UNSIGNED)';
				}
				else
				{
					$table->sortOptions[ $values['sort_by'] ] = $values['sort_by'];
				}

				$table->sortBy = $values['sort_by'];
				$table->sortDirection = $values['sort_direction'];
			}

			/* Cutoff */
			$days = NULL;
			if ( isset( $values['time_frame'] ) )
			{
				switch ( $values['time_frame'] )
				{
					case 'today':
						$days = 1;
						break;
					case 'last_5_days':
						$days = 5;
						break;
					case 'last_7_days':
						$days = 7;
						break;
					case 'last_10_days':
						$days = 10;
						break;
					case 'last_15_days':
						$days = 15;
						break;
					case 'last_20_days':
						$days = 20;
						break;
					case 'last_25_days':
						$days = 25;
						break;
					case 'last_30_days':
						$days = 30;
						break;
					case 'last_60_days':
						$days = 60;
						break;
					case 'last_90_days':
						$days = 90;
						break;
					case 'since_last_visit':
						$table->where[] = array( 'record_last_comment>?', \IPS\Member::loggedIn()->last_visit );
						break;
				}
				if ( $days !== NULL )
				{
					$table->where[] = array( 'record_last_comment>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $days . 'D' ) )->getTimestamp() );
				}
			}
		};

		/* RSS */
		if ( $database->rss )
		{
			$rssUrl  = $table->baseUrl->setQueryString('rss', 1 );
			$rssName = $database->_title . ': ' . $category->metaTitle();
			\IPS\Output::i()->rssFeeds[ $rssName ] = $rssUrl;
			
			/* Show RSS feed */
			if ( isset( \IPS\Request::i()->rss ) )
			{
				$rssName = \IPS\Member::loggedIn()->language()->get('content_db_' . $database->id ) . ': ' . $category->metaTitle();
				$document     = \IPS\Xml\Rss::newDocument( $table->baseUrl, $rssName, $rssName );
				$contentField = 'field_' . $database->field_content;
				
				foreach ( $table->getRows( array() ) as $record )
				{
					if ( ! $record->hidden() )
					{
						$content = $record->$contentField;
						
						if ( $record->record_image )
						{
							$content = \IPS\dcudash\Theme::i()->getTemplate( $category->_template_listing, 'dcudash', 'database' )->rssItemWithImage( $content, $record->record_image );
						}

						$document->addItem( $record->_title, $record->url(), $content, \IPS\DateTime::ts( ( $record->record_last_comment > $record->record_publish_date ) ? $record->record_publish_date : $record->record_last_comment ), $record->_id );
					}
				}
		
				/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
				\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
			}
		}

		/* Update location */
		$permissions = $category->permissions();
		\IPS\Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_dcudash_viewing_db_cat', array( 'content_db_' . $database->id => TRUE, 'content_cat_name_' . $category->id => TRUE ) );

		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'records/list.css', 'dcudash', 'front' ) );

		$stringTable = (string) $table;
		
		\IPS\dcudash\Databases\Dispatcher::i()->output .= \IPS\dcudash\Theme::i()->getTemplate( $category->_template_listing, 'dcudash', 'database' )->categoryHeader( $category, $stringTable, static::$activeFilters );
		
		if ( ( $category->hasChildren() AND $category->show_records ) OR ! $category->hasChildren() )
		{
			\IPS\dcudash\Databases\Dispatcher::i()->output .= $stringTable;
		}
		
		\IPS\Output::i()->title = ( $table->dash > 1 ) ? \IPS\Member::loggedIn()->language()->addToStack( 'title_with_dash_number', FALSE, array( 'sprintf' => array( $category->dashTitle(), $table->dash ) ) ) : $category->dashTitle();
		\IPS\dcudash\Databases\Dispatcher::i()->output .= \IPS\dcudash\Theme::i()->getTemplate( $category->_template_listing, 'dcudash', 'database' )->categoryFooter( $category, $stringTable, static::$activeFilters );
	}
	
	/**
	 * Form
	 *
	 * @return	void
	 */
	public function form()
	{
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_records.js', 'dcudash' ) );

		$database		= \IPS\dcudash\Databases::load( \IPS\dcudash\Databases\Dispatcher::i()->databaseId );
		$recordClass	= '\IPS\dcudash\Records' . \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
		$categoryClass	= '\IPS\dcudash\Categories' . \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
		$category		= $categoryClass::loadAndCheckPerms( \IPS\dcudash\Databases\Dispatcher::i()->categoryId );
		$fieldsClass	= '\IPS\dcudash\Fields' . \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
		$title			= \IPS\Member::loggedIn()->language()->addToStack( 'content_record_form_new_record', FALSE, array( 'sprintf' => array( $database->recordWord( 1, TRUE ) ) ) );

		$form = $recordClass::create( $category );
		$form->class = 'ipsForm_vertical';
	
		$hasModOptions = FALSE;
		
		if ( $recordClass::modPermission( 'lock', NULL, $category ) or
			 $recordClass::modPermission( 'pin', NULL, $category ) or
			 $recordClass::modPermission( 'hide', NULL, $category ) or
			 $recordClass::modPermission( 'feature', NULL, $category ) or
			 $fieldsClass::fixedFieldFormShow( 'record_allow_comments' ) or
			 $fieldsClass::fixedFieldFormShow( 'record_expiry_date' ) or
			 $fieldsClass::fixedFieldFormShow( 'record_comment_cutoff' ) or
			 \IPS\Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			$hasModOptions = TRUE;
		}
		
		\IPS\Output::i()->allowDefaultWidgets = FALSE;
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\dcudash\Dashes\Dash::$currentDash->getWidgets();
		\IPS\dcudash\Databases\Dispatcher::i()->output = \IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\dcudash\Theme::i(), 'getTemplate' ), array( $database->template_form, 'dcudash', 'database' ) ), 'recordForm' ), NULL, $category, $database, \IPS\dcudash\Dashes\Dash::$currentDash, $title, $hasModOptions );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( $title );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'records/form.css', 'dcudash', 'front' ) );

		try
		{
			if ( $database->use_categories )
			{
				foreach( $category->parents() AS $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				\IPS\Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
		}
		catch( \Exception $e ) {}
	
		\IPS\Output::i()->breadcrumb[] = array( NULL, $title );
	}
	
	/**
	 * Mark Read
	 *
	 * @return	void
	 */
	protected function markRead()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$meowBreed = '\IPS\dcudash\Categories' . \IPS\dcudash\Databases\Dispatcher::i()->databaseId;
			$meow      = $meowBreed::load( \IPS\dcudash\Databases\Dispatcher::i()->categoryId );
			\IPS\dcudash\Records::markContainerRead( $meow );
			\IPS\Output::i()->redirect( $meow->url() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'module_no_permission', '2T254/3', 403, '' );
		}
	}

}