<?php
/**
 * @brief		Folder Model
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\Dashes;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Folder Model
 */
class _Folder extends \IPS\Node\Model
{
	/**
	 * Munge different record types
	 *
	 *
	 * @return  array
	 */
	public static function munge()
	{
		$rows = array();
		$args = func_get_args();
	
		foreach( $args as $arg )
		{
			foreach( $arg as $id => $obj )
			{
				$rows[ $obj->getSortableName() . '_' . $obj::$databaseTable . '_' . $obj->id  ] = $obj;
			}
		}
	
		ksort( $rows );
	
		return $rows;
	}
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'dcudash_folders';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'folder_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array('folder_name', 'folder_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Parent ID Root Value
	 * @note	This normally doesn't need changing though some legacy areas use -1 to indicate a root node
	 */
	public static $databaseColumnParentRootValue = 0;
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'path';

	/**
	 * @brief	[Node] Automatically set position for new nodes
	 */
	public static $automaticPositionDetermination = FALSE;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'folder';
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static $subnodeClass = 'IPS\dcudash\Dashes\Dash';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;
	
	protected static $restrictions = array(
 		'app'		=> 'dcudash',
 		'module'	=> 'dashes',
 		'all'		=> 'dash_manage',
 		'prefix'	=> 'dash_'
	);
	
	/**
	 * [Node] Get Title
	 *
	 * @return	string|null
	 */
	protected function get__title()
	{
		return $this->name;
	}
	
	/**
	 * Get sortable name
	 *
	 * @return	string
	 */
	public function getSortableName()
	{
		return $this->name;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );
		$return  = array();
		
		if ( isset( $buttons['copy'] ) )
		{
			unset( $buttons['copy'] );
		}
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['icon']	 = 'folder-open';
			$buttons['add']['title'] = 'content_add_folder';
			$buttons['add']['data']  = array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_folder') );
			$buttons['add']['link']	 = $url->setQueryString( array( 'subnode' => 0, 'do' => 'form', 'parent' => $this->_id ) );
			
			$buttons['add_dash'] = array(
					'icon'	=> 'plus-circle',
					'title'	=> 'content_add_dash',
					'link'	=> $url->setQueryString( array( 'subnode' => 1, 'do' => 'add', 'parent' => $this->_id ) ),
					'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_dash') )
			);
		}
		
		/* Re-arrange */
		if ( isset( $buttons['edit'] ) )
		{
			$return['edit'] = $buttons['edit'];
		}
		
		if ( isset( $buttons['add_dash'] ) )
		{
			$return['add_dash'] = $buttons['add_dash'];
		}
		
		if ( isset( $buttons['add'] ) )
		{
			$return['add'] = $buttons['add'];
		}
			
		if ( isset( $buttons['delete'] ) )
		{
			if ( $this->getItemCount() )
			{
				$return['delete'] = array(
					'icon'	=> 'trash-o',
					'title'	=> 'empty',
					'link'	=> $url->setQueryString( array( 'do' => 'delete', 'id' => $this->_id ) ),
					'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('empty') ),
					'hotkey'=> 'd'
				);
			}
			else
			{
				$return['delete'] = $buttons['delete'];
			}
		}	
		
		return $return;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		/* Build form */
		$form->add( new \IPS\Helpers\Form\Text( 'folder_name', $this->id ? $this->name : '', TRUE, array( 'maxLength' => 64 ), function( $val )
		{
			try
			{
				$testDash = \IPS\dcudash\Dashes\Dash::load( $val, 'dash_seo_name' );
				
				/* Ok, we have a folder, but is it on the same tree as us ?*/
				if ( intval( \IPS\Request::i()->folder_parent_id ) == $testDash->folder_id )
				{
					/* Yep, this will break designers' mode and may confuse the FURL engine so we cannot allow this */
					throw new \InvalidArgumentException('content_folder_name_furl_collision_folder');
				}
			}
			catch ( \OutOfRangeException $e )
			{
				/* Nothing with the same name, so that's proper good that is */
			}
			
			try
			{
				$test = \IPS\dcudash\Dashes\Folder::load( \IPS\Http\Url\Friendly::seoTitle( $val ), 'folder_name' );

				if ( ! empty( \IPS\Request::i()->id ) and $test->id != \IPS\Request::i()->id )
				{
					throw new \InvalidArgumentException('content_folder_name_in_use');
				}
			}
			catch ( \OutOfRangeException $e )
			{
				/* If we hit here, we don't have an existing folder by that name so check for a collision */
				if ( \IPS\Request::i()->folder_parent_id == 0 AND \IPS\dcudash\Dashes\Dash::isFurlCollision( \IPS\Http\Url\Friendly::seoTitle( $val ) ) )
				{
					throw new \InvalidArgumentException('content_folder_name_furl_collision');
				}
			}
		} ) );

		$class = get_called_class();

		$form->add( new \IPS\Helpers\Form\Node( 'folder_parent_id', $this->parent_id ? $this->parent_id : 0, FALSE, array(
				'class'         => '\IPS\dcudash\Dashes\Folder',
				'zeroVal'         => 'node_no_parent',
				'permissionCheck' => function( $node ) use ( $class )
				{
					if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
					{
						return FALSE;
					}

					return !isset( \IPS\Request::i()->id ) or ( $node->id != \IPS\Request::i()->id and !$node->isChildOf( $node::load( \IPS\Request::i()->id ) ) );
				}
		) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		$isNew = $this->_new;

		if ( !$this->id )
		{
			$this->save();
		}
		
		$this->origParentId = $this->parent_id;
		$this->origName     = $this->name;
		
		if ( isset( $values['folder_parent_id'] ) AND ( ! empty( $values['folder_parent_id'] ) OR $values['folder_parent_id'] === 0 ) )
		{
			$values['parent_id'] = ( $values['folder_parent_id'] === 0 ) ? 0 : $values['folder_parent_id']->id;
			unset( $values['folder_parent_id'] );
		}
		
		if( isset( $values['folder_name'] ) )
		{
			$values['name'] = \IPS\Http\Url\Friendly::seoTitle( $values['folder_name'] );
			unset( $values['folder_name'] );
		}

		if ( ! $isNew and ( $this->parent_id !== $values['parent_id'] or $this->name !== $values['name'] ) )
		{
			$this->storeUrl();
		}

		return $values;
	}

	/**
	 * @brief	Original parent ID
	 */
	protected $origParentId;

	/**
	 * @brief	Original Name
	 */
	protected $origName;

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		if ( $this->origParentId !== $values['parent_id'] OR $this->origName !== $values['name'] )
		{
			$this->resetPath( true );
		}
	}

	/**
	 * Stores the URL so when its changed, the old can 301 to the new location
	 *
	 * @return void
	 */
	public function storeUrl()
	{
		\IPS\Db::i()->insert( 'dcudash_url_store', array(
			'store_path'       => $this->path,
			'store_current_id' => $this->_id,
			'store_type'       => 'folder'
		) );
	}

	/**
	 * Save a folder
	 * 
	 * @return void
	 */
	public function save()
	{
		$this->last_modified = time();
		
		parent::save();
	}
	
	/**
	 * Retrieve item count (if applicable) for a node.
	 *
	 * @return	int|bool
	 */
	public function getItemCount()
	{
		$dashes = (int) \IPS\Db::i()->select( 'COUNT(*)', 'dcudash_dashes', array( 'dash_folder_id=?', $this->id ) )->first();
		$subFolders = (int) \IPS\Db::i()->select( 'COUNT(*)', 'dcudash_folders', array( 'folder_parent_id=?', $this->id ) )->first();

		return $dashes + $subFolders;
	}
	
	/**
	 * Form to delete or move content
	 *
	 * @param	bool	$showMoveToChildren	If TRUE, will show "move to children" even if there are no children
	 * @return	\IPS\Helpers\Form
	 */
	public function deleteOrMoveForm( $showMoveToChildren=FALSE )
	{
		$hasContent = $this->getItemCount();
			
		$form = new \IPS\Helpers\Form( 'form', 'delete' );
		
		if ( $hasContent )
		{
			$form->add( new \IPS\Helpers\Form\Node( 'dcudash_move_dashes', 0, TRUE, array( 'class' => get_class( $this ), 'disabled' => array( $this->_id ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'dcudash_move_to_root', 'subnodes' => FALSE, 'permissionCheck' => function( $node )
			{
				return \IPS\Request::i()->id != $node->id;
			} ) ) );
		}
		
		return $form;
	}
	
	/**
	 * Handle submissions of form to delete or move content
	 *
	 * @param	array	$values			Values from form
	 * @return	void
	 */
	public function deleteOrMoveFormSubmit( $values )
	{
		if ( $values['dcudash_move_dashes'] )
		{
			$folderId = $values['dcudash_move_dashes']->_id;
		}
		else
		{
			$folderId = 0;
		}
		
		\IPS\Db::i()->update( 'dcudash_dashes', array( 'dash_folder_id' => $folderId ), array( 'dash_folder_id=?', $this->_id ) );
		\IPS\Db::i()->update( 'dcudash_folders', array( 'folder_parent_id' => $folderId ), array( 'folder_id=?', $this->_id ) );
		\IPS\Db::i()->update( 'dcudash_folders', array( 'folder_parent_id' => $folderId ), array( 'folder_parent_id=?', $this->_id ) );
		
		unset( \IPS\Data\Store::i()->dashes_dash_urls );
		
		/* Update dashes */
		\IPS\dcudash\Dashes\Dash::resetPath( $folderId );
		
		/* Delete it */
		\IPS\Session::i()->log( 'acplog__node_deleted', array( $this->title => TRUE, $this->titleForLog() => FALSE ) );
		$this->delete();
	}
	
	/**
	 * Resets the stored path
	 * 
	 * @param	boolean	$recursivelyCheck	Recursively reset up and down the tree
	 * @return void
	 */
	public function resetPath( $recursivelyCheck=true )
	{
		$path = array();
		
		foreach( $this->parents() as $obj )
		{
			$path[] = $obj->name;
		}
		
		$this->path = ( count( $path ) ) ? implode( '/', $path ) . '/' . $this->name : $this->name;
		
		/* Save path update */
		parent::save();
		
		/* Update dashes */
		\IPS\dcudash\Dashes\Dash::resetPath( $this->id );
		
		if ( $recursivelyCheck === true )
		{
			/* Fix children */
			foreach( $this->children( NULL, NULL, FALSE ) as $child )
			{
				$child->resetPath( false );
				$child->_recursivelyResetChildPaths();
			}
			
			/* Fix parents */
			foreach( $this->parents() as $parent )
			{
				$parent->resetPath( false );
			}
		}
	}
	
	/**
	 * Recurse through the node tree to reset kids
	 * 
	 * @return void
	 */
	protected function _recursivelyResetChildPaths()
	{
		foreach( $this->children( NULL, NULL, FALSE ) as $child )
		{
			$child->resetPath( false );
			$child->_recursivelyResetChildPaths();
		}
	}
}