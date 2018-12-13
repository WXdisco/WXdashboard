<?php
/**
 * @brief		Media Model
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
 * @brief Media Model
 */
class _Media extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'dcudash_media';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'media_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array('media_full_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static $parentNodeColumnId = 'parent';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static $parentNodeClass = 'IPS\dcudash\Media\Folder';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnOrder = 'filename';

	/**
	 * @brief	[Node] Automatically set position for new nodes
	 */
	public static $automaticPositionDetermination = FALSE;
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;
	
	/**
	 * @brief	[Node] Title
	 */
	public static $nodeTitle = 'dash';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 array(
	 'app'		=> 'core',				// The application key which holds the restrictrions
	 'module'	=> 'foo',				// The module key which holds the restrictions
	 'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 'add'			=> 'foo_add',
	 'edit'			=> 'foo_edit',
	 'permissions'	=> 'foo_perms',
	 'delete'		=> 'foo_delete'
	 ),
	 'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
			'app'		=> 'dcudash',
			'module'	=> 'dashes',
			'prefix' 	=> 'media_'
	);

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->parent     = 0;
		$this->full_path  = '';
	}
	
	/**
	 * Resets a media path
	 *
	 * @param 	int 	$folderId	Folder ID to reset
	 * @return	void
	 */
	public static function resetPath( $folderId )
	{
		try
		{
			$path = \IPS\dcudash\Media\Folder::load( $folderId )->path;
		}
		catch ( \OutOfRangeException $ex )
		{
			throw new \OutOfRangeException;
		}
	
		$children = static::getChildren( $folderId );
	
		foreach( $children as $id => $obj )
		{
			$obj->setFullPath( $path );
		}
	}
	
	/**
	 * Get all children of a specific folder.
	 *
	 * @param	INT 	$folderId		Folder ID to fetch children from
	 * @return	array
	 */
	public static function getChildren( $folderId=0 )
	{
		$children = array();
		foreach( \IPS\Db::i()->select( '*', static::$databaseTable, array( 'media_parent=?', intval( $folderId ) ), 'media_filename ASC' ) as $child )
		{
			$children[ $child[ static::$databasePrefix . static::$databaseColumnId ] ] = static::load( $child[ static::$databasePrefix . static::$databaseColumnId ] );
		}
	
		return $children;
	}
	
	/**
	 * Delete media by file ids
	 *
	 * @param	array	$ids	Array of IDs to remove
	 * @return	void
	 */
	public static function deleteByFileIds( $ids=array() )
	{
		foreach( $ids as $id )
		{
			try
			{
				static::load( $id )->delete();
			}
			catch( \Exception $ex ) { }
		}
	}
	
	/**
	 * Get URL
	 *
	 * @return \IPS\Http\Url object
	 */
	public function url()
	{
		if ( \IPS\Theme::designersModeEnabled() )
		{
			return \IPS\Settings::i()->base_url . 'themes/dcudash/media/' . $this->full_path;
		}
		else
		{
			return (string)\IPS\File::get( 'dcudash_Media', $this->file_object )->url;
		}
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		try
		{
			if ( $this->file_object )
			{
				\IPS\File::get( 'dcudash_Media', $this->file_object )->delete();
			}
		}
		catch( \Exception $ex ) { }

		parent::delete();
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
		$delete  = NULL;

		if ( isset( $buttons['add' ] ) )
		{
			unset( $buttons['add'] );
		}

		if ( isset( $buttons['delete'] ) )
		{
			$delete = $buttons['delete'];
			unset( $buttons['delete'] );
		}

		if ( isset( $buttons['copy' ] ) )
		{
			unset( $buttons['copy'] );
		}

		$buttons['key'] = array(
			'icon'	=> 'file-code-o',
			'title'	=> 'dcudash_media_key',
			'link'	=> \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=media&do=key&id=' . $this->id ),
			'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('dcudash_media_key') )
		);

		if ( $this->is_image )
		{
			$buttons['preview'] = array(
				'icon'	=> 'search',
				'title'	=> 'dcudash_media_preview',
				'link'	=> \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=media&do=preview&id=' . $this->id ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('dcudash_media_preview') )
			);
		}

		if ( $delete )
		{
			$buttons['delete'] = $delete;
		}

		return $buttons;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @note	This is not used currently. See \IPS\dcudash\modules\admin\media.php upload()
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		/* Build form */
		$form->add( new \IPS\Helpers\Form\Upload( 'media_filename', ( ( $this->filename ) ? \IPS\File::get( 'dcudash_Media', $this->file_object ) : NULL ), FALSE, array( 'obscure' => FALSE, 'maxFileSize' => 5, 'storageExtension' => 'dcudash_Media', 'storageContainer' => 'dashes_media' ), NULL, NULL, NULL, 'media_filename' ) );
			
		$form->add( new \IPS\Helpers\Form\Node( 'media_parent', $this->parent ? $this->parent : 0, FALSE, array(
			'class'    => '\IPS\dcudash\Media\Folder',
			'zeroVal'  => 'node_no_parent'
		) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @note	This is not used currently. See \IPS\dcudash\modules\admin\media.php upload()
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( isset( $values['media_parent'] ) AND ( ! empty( $values['media_parent'] ) OR $values['media_parent'] === 0 ) )
		{
			$values['parent'] = ( $values['media_parent'] === 0 ) ? 0 : $values['media_parent']->id;
			unset( $values['media_parent'] );
		}
		
		if ( isset( $values['media_filename'] ) )
		{
			$filename = $values['media_filename']->originalFilename;

			if ( ! $this->_new and $this->file_object )
			{
				$prefix = $this->parent . '_';

				if ( mb_strstr( $filename, $prefix ) )
				{
					$filename = mb_substr( $filename, mb_strlen( $prefix ) );
				}
			}

			$values['filename']        = $filename;
			$values['filename_stored'] = $values['parent'] . '_' . $values['filename'];
			$values['is_image']        = $values['media_filename']->isImage();

			/* We need to rename it */
			try
			{
				$values['media_filename']->delete();
			}
			catch( \Exception $ex ) { }

			$values['file_object'] = (string) \IPS\File::create( 'dcudash_Media', $values['filename_stored'], $values['media_filename']->contents(), 'dashes_media', TRUE, NULL, FALSE );

			unset( $values['media_filename'] );
		}

		if ( $this->_new )
		{
			$values['added'] = time();
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @note	This is not used currently. See \IPS\dcudash\modules\admin\media.php upload()
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		$this->setFullPath( ( $this->parent ? \IPS\dcudash\Media\Folder::load( $this->parent )->path : '' ) );
		$this->save();
	}

	/**
	 * Get sortable name
	 *
	 * @return	string
	 */
	public function getSortableName()
	{
		return $this->full_path;
	}
	
	/**
	 * Resets a folder path
	 *
	 * @param	string	$path	Path to reset
	 * @return	void
	 */
	public function setFullPath( $path )
	{
		$this->full_path = trim( $path . '/' . $this->filename, '/' );
		$this->save();
	}

	/**
	 * Write media to disk for designer's mode
	 *
	 * @return void
	 */
	public static function exportDesignersModeMedia()
	{
		/* Make sure our media folder exists */
		if ( !is_dir( \IPS\ROOT_PATH . '/themes/dcudash/media' ) )
		{
			mkdir( \IPS\ROOT_PATH . '/themes/dcudash/media', \IPS\IPS_FOLDER_PERMISSION );
			chmod( \IPS\ROOT_PATH . '/themes/dcudash/media', \IPS\IPS_FOLDER_PERMISSION );
		}
		
		foreach( \IPS\Db::i()->select( '*', 'dcudash_media' ) as $media )
		{
			/* We could use recursive mode but it wouldn't correctly chmod the intermediate dirs */
			$bits = explode( '/', "/themes/dcudash/media/" . $media['media_full_path'] );
			$dir = '';

			$filename = array_pop( $bits );

			foreach( $bits as $part )
			{
				$dir .= $part . '/';

				if ( ! is_dir( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ) ) )
				{
					mkdir( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ), \IPS\IPS_FOLDER_PERMISSION );
					chmod( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ), \IPS\IPS_FOLDER_PERMISSION );
				}
			}
			
			try
			{
				\file_put_contents( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ) . '/' . $filename, \IPS\File::get( 'dcudash_Media', $media['media_file_object'] )->contents() );
				@chmod( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ) . '/' . $filename, \IPS\IPS_FILE_PERMISSION );
			}
			catch( \RuntimeException $e ) { }
		}
	}

	/**
	 * Removes folders that are empty
	 *
	 * @return void
	 */
	public static function removeEmptyFolders()
	{
		$folders    = iterator_to_array( \IPS\Db::i()->select( 'DISTINCT(media_parent)', 'dcudash_media', array( 'media_parent > 0' ) ) );
		$allFolders = iterator_to_array( \IPS\Db::i()->select( '*', 'dcudash_media_folders' )->setKeyField( 'media_folder_id' ) );

		foreach( $folders as $id )
		{
			if ( isset( $allFolders[ $id ] ) )
			{
				$currentParent = $allFolders[ $id ]['media_folder_parent'];
				$try = 0;
				while( $currentParent )
				{
					if ( $try++ > 50 )
					{
						/* Prevent broken associations from preventing execution */
						break;
					}

					if ( ! in_array( $currentParent, $folders ) )
					{
						$folders[] = $currentParent;
					}

					$currentParent = $allFolders[ $currentParent ]['media_folder_parent'];
				}
			}
		}

		\IPS\Db::i()->delete( 'dcudash_media_folders', array( \IPS\Db::i()->in( 'media_folder_id', array_values( $folders ), TRUE ) ) );
	}

	/**
	 * Import media from disk for designer's mode
	 *
	 * @return void
	 */
	public static function importDesignersModeMedia()
	{
		$path = \IPS\ROOT_PATH . '/themes/dcudash/media';
		$seen = array();

		if ( is_dir( $path ) )
		{
			static::importDesignersModeMediaRecurse( $seen, $path );
		}

		\IPS\Db::i()->delete( 'dcudash_media', array( \IPS\Db::i()->in( 'media_id', $seen, TRUE ) ) );
		static::removeEmptyFolders();
	}

	/**
	 * Import media from disk for designer's mode recursive method
	 *
	 * @return void
	 */
	public static function importDesignersModeMediaRecurse( &$seen, $path )
	{
		if ( is_dir( $path ) )
		{
			foreach ( new \DirectoryIterator( $path ) as $dir )
			{
				if ( $dir->isDot() || mb_substr( $dir->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}

				if ( $dir->isDir() )
				{
					static::importDesignersModeMediaRecurse( $seen, $path . '/' . $dir->getFilename() );
				}
				else
				{
					$contents = \file_get_contents( $dir->getRealPath() );

					/* Create */
					$seen[] = static::createMedia( trim( str_replace( str_replace( '\\', '/', \IPS\ROOT_PATH ) . '/themes/dcudash/media', '', str_replace( '\\', '/', $dir->getRealPath() ) ), '/' ), $contents );
				}
			}
		}
	}

	/**
	 * Create new media file from a disk file. If the file exists and is unchanged, it will not be updated
	 *
	 * @param   string      $path       File path (/folder/file.txt)
	 * @param   string      $contents   File contents
	 * @return  int         ID of existing media or of new media
	 */
	public static function createMedia( $path, $contents )
	{
		try
		{
			$test = static::load( $path, 'media_full_path' );

			$test->file_object = \IPS\File::create( 'dcudash_Media', $test->filename_stored, $contents, 'dashes_media', TRUE, NULL, FALSE );
			$test->save();

			return $test->id;
		}
		catch( \RuntimeException $ex )
		{
			try
			{
				\IPS\File::get( 'dcudash_Media', $path );
			}
			catch( \Exception $x )
			{
				/* File doesn't exist already */
				throw $ex;
			}
		}
		catch( \OutOfRangeException $ex )
		{
			/* It doesn't exist */
			$exploded = explode( '/', $path );
			$filename = array_pop( $exploded );
			$folderId = 0;

			if ( count( $exploded ) )
			{
				$testDir = trim( implode( '/', $exploded ), '/' );
				try
				{
					$test = \IPS\dcudash\Media\Folder::load( $testDir, 'media_folder_path' );

					/* Yep */
					$folderId = $test->id;
				}
				catch( \OutOfRangeException $ex )
				{
					$testDir  = '';
					foreach( $exploded as $dir )
					{
						$testDir = trim( $testDir . '/' . $dir, '/' );

						try
						{
							$test     = \IPS\dcudash\Media\Folder::load( $testDir, 'media_folder_path' );
							$folderId = $test->id;
						}
						catch( \OutOfRangeException $ex )
						{
							$folder = new \IPS\dcudash\Media\Folder;
							$folder->parent = $folderId;
							$folder->name   = $dir;
							$folder->path   = $testDir;
							$folder->save();
							$folderId = $folder->id;
						}
					}
				}
			}

			$media = new \IPS\dcudash\Media;
			$media->parent          = $folderId;
			$media->filename        = $filename;
			$media->added           = time();
			$media->full_path       = $path;
			$media->filename_stored = $folderId . '_' . $filename;
			$media->file_object     = \IPS\File::create( 'dcudash_Media', $media->filename_stored, $contents, 'dashes_media', TRUE, NULL, FALSE );
			$media->is_image        = $media->file_object->isImage();
			$media->save();

			return $media->id;
		}
	}
}