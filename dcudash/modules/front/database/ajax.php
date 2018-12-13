<?php
/**
 * @brief		Ajax only methods
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
 * Ajax only methods
 */
class _ajax extends \IPS\Dispatcher\Controller
{
	/**
	 * Return a FURL
	 *
	 * @return	void
	 */
	protected function makeFurl()
	{
		return \IPS\Output::i()->json( array( 'slug' => \IPS\Http\Url\Friendly::seoTitle( \IPS\Request::i()->slug ) ) );
	}

	/**
	 * Find Record
	 *
	 * @retun	void
	 */
	public function findRecord()
	{
		$results  = array();
		$database = \IPS\dcudash\Databases::load( \IPS\Request::i()->id );
		$input    = mb_strtolower( \IPS\Request::i()->input );
		$field    = "field_" . $database->field_title;
		$class    = '\IPS\dcudash\Records' . $database->id;
		$category = '';

		$where = array( $field . " LIKE CONCAT('%', ?, '%')" );
		$binds = array( $input );

		foreach ( \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $database->id, array_merge( array( implode( ' OR ', $where ) ), $binds ), 'LENGTH(' . $field . ') ASC', array( 0, 20 ) ) as $row )
		{
			$record = $class::constructFromData( $row );
			
			if ( ! $record->canView() )
			{
				continue;
			}
			
			if ( $database->use_categories )
			{
				$category = \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_autocomplete_category', NULL, array( 'sprintf' => array( $record->container()->_title ) ) );
			}

			$results[] = array(
				'id'	   => $record->_id,
				'value'    => $record->_title,
				'category' => $category,
				'date'	   => \IPS\DateTime::ts( $record->record_publish_date )->html(),
			);
		}

		\IPS\Output::i()->json( $results );
	}
	
}