<?php
/**
 * @brief		DatabaseFilters Widget
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
 * LatestDashboards Widget
 */
class _DatabaseFilters extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'DatabaseFilters';
	
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
		/* Viewing or adding/editing a record */
		if ( \IPS\dcudash\Databases\Dispatcher::i()->recordId or \IPS\Request::i()->do == 'form' )
		{
			return '';
		}

		if ( ! \IPS\dcudash\Databases\Dispatcher::i()->databaseId AND ! \IPS\dcudash\Databases\Dispatcher::i()->categoryId )
		{
			return '';
		}
		
		try
		{
			$database = \IPS\dcudash\Databases::load( \IPS\dcudash\Databases\Dispatcher::i()->databaseId );
			$database->preLoadWords();
		}
		catch ( \OutOfRangeException $e )
		{
			return '';
		}
		
		try
		{
			$category = \IPS\dcudash\Categories::load( \IPS\dcudash\Databases\Dispatcher::i()->categoryId );
		}
		catch ( \OutOfRangeException $e )
		{
			return '';
		}
		
		if ( ! $database->use_categories AND $database->cat_index_type !== 0 )
		{
			return '';
		}
		
		$fieldClass = 'IPS\dcudash\Fields' . $database->id;
		
		$fields = array();
		$cookie = $category->getFilterCookie();
		$cookieValues = ( $cookie !== NULL ) ? array_combine( array_map( function( $k ) { return "field_" . $k; }, array_keys( $cookie ) ), $cookie ) : array();

		$urlValues = array();

		foreach( \IPS\Request::i() as $k => $v )
		{
			if( mb_strpos( $k, 'content_field_' ) !== FALSE )
			{
				/* YesNo fields come in as _checkbox */
				if ( mb_substr( $k, -9 ) === '_checkbox' )
				{
					$k = mb_substr( $k, 0, -9 );
				}
				
				$urlValues[ str_replace( 'content_', '', $k ) ] = is_array( $v ) ? implode( ',', $v ) : $v;
			}
		}

		$cookieValues = array_merge( $urlValues, $cookieValues );

		foreach( $fieldClass::fields( $cookieValues, 'view', $category, $fieldClass::FIELD_SKIP_TITLE_CONTENT | $fieldClass::FIELD_DISPLAY_FILTERS ) as $id => $field )
		{
			$fields[ $id ] = $field;
		}
		
		if ( count( $fields ) )
		{
			$form = new \IPS\Helpers\Form( 'category_filters', 'update', $category->url() );
			$form->class = 'ipsForm_vertical'; 
			if ( \IPS\Request::i()->sortby )
			{
				$form->hiddenValues['sortby']		 = \IPS\Request::i()->sortby;
				$form->hiddenValues['sortdirection'] = isset( \IPS\Request::i()->sortdirection ) ? \IPS\Request::i()->sortdirection : 'desc';
			}
			else
			{
				$form->hiddenValues['sortby']		 = $database->field_sort;
				$form->hiddenValues['sortdirection'] = $database->field_direction;
			}
			
			$form->hiddenValues['record_type'] = 'all';
			$form->hiddenValues['time_frame'] = 'show_all';
			
			foreach( $fields as $id => $field )
			{
				$form->add( $field );
			}

			$form->add( new \IPS\Helpers\Form\Checkbox( 'dcudash_widget_filters_remember', ( $cookie !== NULL ) ? TRUE : FALSE , FALSE, array( 'label' => 'dcudash_widget_filters_remember_text') ) );
			
			if ( $values = $form->values() )
			{
				$url    = $category->url()->setQueryString( array( 'advanced_search_submitted' => 1, 'csrfKey' => \IPS\Session::i()->csrfKey ) );
				$cookie = array();
				$params = array();
				foreach( $values as $k => $v )
				{
					if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
					{
						$id = mb_substr( $k, 14 );
						
						if ( isset( $fields[ $id ] ) and $fields[ $id ] instanceof \IPS\Helpers\Form\CheckboxSet )
						{
							/* We need to reformat this a little */
							$v = array_combine( $v, $v );
						}
						else if ( isset( $fields[ $id ] ) and $fields[ $id ] instanceof \IPS\Helpers\Form\YesNo )
						{
							/* The form class looks for {$name}_checkbox to determine the value */
							$k = $k . '_checkbox';
						}
						else if ( isset( $fields[ $id ] ) and $fields[ $id ] instanceof \IPS\Helpers\Form\DateRange )
						{
							/* We need to reformat this a little */
							$start = ( $v['start'] instanceof \IPS\DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
							$end   = ( $v['end'] instanceof \IPS\DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );
							$v = array( 'start' => $start, 'end' => $end );
						}
						
						$cookie[ $id ] = $v;
						$params[ $k ] = $v;
					}
				}
				
				if ( count( $form->hiddenValues ) )
				{
					foreach( $form->hiddenValues as $k => $v )
					{
						if ( $k !== 'csrfKey' )
						{
							if ( !in_array( $k, array( 'sortby', 'sortdirection' ) ) )
							{
								$cookie[ $k ] = $v;
							}
							$params[ $k ] = $v;
						}
					}
				}
				
				if ( $values['dcudash_widget_filters_remember'] )
				{
					$category->saveFilterCookie( $cookie );
					\IPS\Output::i()->redirect( $category->url() );
				}
				else
				{
					\IPS\Output::i()->redirect( $url->setQueryString( $params ) );
				}
			}
			
			return $this->output( $database, $category, $form );
		}
		else
		{
			return '';
		}
	}
}