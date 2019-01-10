<?php
/**
 * @brief		Fields Model
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

namespace IPS\dcudash\modules\admin\databases;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * categories
 */
class _categories extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\dcudash\Categories';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		/* This controller can not be accessed without a database ID */
		if( !\IPS\Request::i()->database_id )
		{
			\IPS\Output::i()->error( 'node_error', '2S390/1', 404, '' );
		}

		$this->url = $this->url->setQueryString( array( 'database_id' => \IPS\Request::i()->database_id ) );
		
		/* Assign the correct nodeClass so contentItem is specified */
		$this->nodeClass = '\IPS\dcudash\Categories' . \IPS\Request::i()->database_id;
		
		\IPS\Dispatcher::i()->checkAcpPermission( 'categories_manage' );
		
		$nodeClass = $this->nodeClass;

		$childLang = \IPS\Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle . '_add_child' );
		$nodeClass::$nodeTitle = \IPS\Member::loggedIn()->language()->addToStack('content_cat_db_title', FALSE, array( 'sprintf' => array( \IPS\dcudash\Databases::load( \IPS\Request::i()->database_id )->_title ) ) );
		\IPS\Member::loggedIn()->language()->words[ $nodeClass::$nodeTitle . '_add_child' ] = $childLang;
		parent::execute();
	}
	
	/**
	 * Get Root Rows
	 *
	 * @return	array
	 */
	public function _getRoots()
	{
		$nodeClass = $this->nodeClass;
		$rows = array();
	
		foreach( $nodeClass::roots( NULL ) as $node )
		{
			if ( $node->database_id == \IPS\Request::i()->database_id )
			{
				$rows[ $node->_id ] = $this->_getRow( $node );
			}
		}
	
		return $rows;
	}

	/**
	 * Function to execute after nodes are reordered. Do nothing by default but plugins can extend.
	 *
	 * @param	array	$order	The new ordering that was saved
	 * @return	void
	 * @note	Dashes needs to readjust category_full_path values when a category is moved to a different category
	 */
	protected function _afterReorder( $order )
	{
		$categoryClass = $this->nodeClass;

		foreach( $order as $parent => $nodes )
		{
			foreach ( $nodes as $id => $position )
			{
				$categoryClass::resetPath( $id );
			}
		}

		return parent::_afterReorder( $order );
	}
}