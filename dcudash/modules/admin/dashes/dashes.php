<?php
/**
* @brief		Dashes Controller
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\modules\admin\dashes;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
exit;
}

/**
* Dash management
*/
class _dashes extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\dcudash\Dashes\Folder';
	
	/**
	 * Store the database dash map to prevent many queries
	 */
	protected static $dashToDatabaseMap = NULL;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dash_manage' );
		parent::execute();
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$nodeClass = $this->nodeClass;
		$buttons   = array();

		return $buttons;
	}

	/**
	 * Show the dashes tree
	 *
	 * @return	string
	 */
	protected function manage()
	{
		$url = \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dashes" );
		static::$dashToDatabaseMap = iterator_to_array( \IPS\Db::i()->select( 'database_id, database_dash_id', 'dcudash_databases', array( 'database_dash_id > 0' ) )->setKeyField('database_dash_id')->setValueField('database_id') );
		
		/* Display the table */
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('menu__dcudash_dashes_dashes');
		\IPS\Output::i()->output = new \IPS\Helpers\Tree\Tree( $url, 'menu__dcudash_dashes_dashes',
			/* Get Roots */
			function () use ( $url )
			{
				$data = \IPS\dcudash\modules\admin\dashes\dashes::getRowsForTree( 0 );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					$rows[ $id ] = ( $row instanceof \IPS\dcudash\Dashes\Dash ) ? \IPS\dcudash\modules\admin\dashes\dashes::getDashRow( $row, $url ) : \IPS\dcudash\modules\admin\dashes\dashes::getFolderRow( $row, $url );
				}

				return $rows;
			},
			/* Get Row */
			function ( $id, $root ) use ( $url )
			{
				if ( $root )
				{
					return \IPS\dcudash\modules\admin\dashes\dashes::getFolderRow( \IPS\dcudash\Dashes\Folder::load( $id ), $url );
				}
				else
				{
					return \IPS\dcudash\modules\admin\dashes\dashes::getDashRow( \IPS\dcudash\Dashes\Dash::load( $id ), $url );
				}
			},
			/* Get Row Parent ID*/
			function ()
			{
				return NULL;
			},
			/* Get Children */
			function ( $id ) use ( $url )
			{
				$rows = array();
				$data = \IPS\dcudash\modules\admin\dashes\dashes::getRowsForTree( $id );

				if ( ! isset( \IPS\Request::i()->subnode ) )
				{
					foreach ( $data as $id => $row )
					{
						$rows[ $id ] = ( $row instanceof \IPS\dcudash\Dashes\Dash ) ? \IPS\dcudash\modules\admin\dashes\dashes::getDashRow( $row, $url ) : \IPS\dcudash\modules\admin\dashes\dashes::getFolderRow( $row, $url );
					}
				}
				return $rows;
			},
           array( $this, '_getRootButtons' ),
           TRUE,
           FALSE,
           FALSE
		);
		
		/* Add a button for managing DB settings */
		\IPS\Output::i()->sidebar['actions']['dashessettings'] = array(
			'title'		=> 'dcudash_dashes_settings',
			'icon'		=> 'wrench',
			'link'		=> \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=dashes&do=settings' ),
			'data'	    => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('dcudash_dashes_settings') )
		);

		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'dcudash', 'dashes', 'dash_add' )  )
		{
			\IPS\Output::i()->sidebar['actions']['add_folder'] = array(
				'primary'	=> true,
				'icon'	=> 'folder-open',
				'title'	=> 'content_add_folder',
				'link'	=> \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=dashes&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_folder') )
			);

			\IPS\Output::i()->sidebar['actions']['add_dash'] = array(
				'primary'	=> true,
				'icon'	=> 'plus-circle',
				'title'	=> 'content_add_dash',
				'link'	=>  \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=dashes&subnode=1&do=add' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_dash') )
			);
		}
	}
	
	/**
	 * Dash settings form
	 *
	 * @return void
	 */
	protected function settings()
	{
		$url 	  = parse_url( \IPS\Settings::i()->base_url );
		$disabled = FALSE;
		$options  = array();
		$url['path'] = preg_replace( '#^/?(.+?)?/?$#', '\1', $url['path'] );
		
		$disabled = ( \IPS\Settings::i()->dcudash_use_different_gateway or $url['path'] ) ? FALSE : TRUE;
		$dirs     = explode( '/', $url['path'] );
		
		if ( count( $dirs ) )
		{
			array_pop( $dirs );
			$base = $url['scheme'] . '://' . $url['host'];
			if ( isset( $url['port'] ) )
			{
				$base .= ':' .$url['port'];
			}

			$base .= '/';
			$options[ $base ] = $base;
			foreach( $dirs as $dir )
			{
				$base .= $dir . '/'; 
				$options[ $base ] = $base;
			}
		}
		
		if ( $disabled )
		{
			\IPS\Member::loggedIn()->language()->words['dcudash_use_different_gateway_warning'] = \IPS\Member::loggedIn()->language()->addToStack('dcudash_dashes_different_gateway_impossible');
		}
		
		if ( \IPS\Settings::i()->htaccess_mod_rewrite )
		{
			\IPS\Member::loggedIn()->language()->words['dcudash_root_dash_url_desc'] = \IPS\Member::loggedIn()->language()->addToStack('dcudash_root_dash_url_rewrite_desc');
		}
		
		$form = new \IPS\Helpers\Form( 'form', 'save' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'dcudash_use_different_gateway', \IPS\Settings::i()->dcudash_use_different_gateway, FALSE, array( 'togglesOn' => array( 'dcudash_root_dash_url' ), 'disabled' => $disabled ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'dcudash_root_dash_url', \IPS\Settings::i()->dcudash_root_dash_url, FALSE, array( 'options' => $options ), function( $val )
		{
			if ( $val and \IPS\Request::i()->dcudash_use_different_gateway )
			{
				if ( mb_substr( $val, -1 ) !== '/' )
				{
					$val .= '/';
				}
				
				$dash = \IPS\dcudash\Dashes\Dash::getDefaultDash();
				
				$response = \IPS\Http\Url::external( ( \IPS\Settings::i()->htaccess_mod_rewrite ? $val . $dash->full_path : $val . 'index.php?/' . $dash->full_path ) )->request( NULL, NULL, FALSE )->get();
				
				if ( $response->httpResponseCode != 200 and $response->httpResponseCode != 303 and ( \IPS\Settings::i()->site_online OR $response->httpResponseCode != 503 ) )
				{
					if ( \IPS\Settings::i()->htaccess_mod_rewrite )
					{
						throw new \LogicException( 'dashes_different_gateway_load_error_rewrite' );
					}
					else
					{
						throw new \LogicException( 'dashes_different_gateway_load_error' );
					}
				}
			}
		}, NULL, NULL, 'dcudash_root_dash_url' ) );

		$form->add( new \IPS\Helpers\Form\Node( 'dcudash_error_dash', \IPS\Settings::i()->dcudash_error_dash ? \IPS\Settings::i()->dcudash_error_dash : 0, FALSE,array(
			'class'           => '\IPS\dcudash\Dashes\Dash',
			'zeroVal'         => 'dcudash_error_dash_none',
			'subnodes'		  => true,
			'permissionCheck' => function( $node )
			{
				return $node->type == 'html';
			}
		) ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Member::clearCreateMenu();
									
			/* Clear Sidebar Caches */
			\IPS\Widget::deleteCaches();
			
			/* Possible gateway choice changed and thusly menu and dash_urls will change */
			if ( isset( \IPS\Data\Store::i()->dashes_dash_urls ) )
			{
				unset( \IPS\Data\Store::i()->dashes_dash_urls  );
			}
			
			if ( isset( \IPS\Data\Store::i()->frontNavigation ) )
			{
				unset( \IPS\Data\Store::i()->frontNavigation  );
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dashes" ), 'saved' );
		}
	
		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( \IPS\Member::loggedIn()->language()->addToStack('dcudash_dashes_settings'), $form, FALSE );
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('dcudash_dashes_settings');
	}
	
	/**
	 * Download .htaccess file
	 *
	 * @return	void
	 */
	protected function htaccess()
	{
		$dir = str_replace( \IPS\CP_DIRECTORY . '/index.php', '', $_SERVER['PHP_SELF'] );
		$dirs = explode( '/', trim( $dir, '/' ) );
		
		if ( count( $dirs ) )
		{
			array_pop( $dirs );
			$dir = implode( '/', $dirs );
			
			if ( ! $dir )
			{
				$dir = '/';
			}
		}
		
		$path = $dir . 'index.php';
		
		if( \strpos( $dir, ' ' ) !== FALSE )
		{
			$dir = '"' . $dir . '"';
			$path = '"' . $path . '"';
		}


		$htaccess = <<<FILE
<IfModule mod_rewrite.c>
Options -MultiViews
RewriteEngine On
RewriteBase {$dir}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule \\.(js|css|jpeg|jpg|gif|png|ico)(\\?|$) - [L,NC,R=404]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$path} [L]
</IfModule>
FILE;

		\IPS\Output::i()->sendOutput( $htaccess, 200, 'application/x-htaccess', array( 'Content-Disposition' => 'attachment; filename=.htaccess' ) );
	}

	/**
	 * Dash content form
	 *
	 * @return void
	 */
	protected function add()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dash_add' );

		$form = new \IPS\Helpers\Form( 'form', 'next' );
		$form->hiddenValues['parent'] = ( isset( \IPS\Request::i()->parent ) ) ? \IPS\Request::i()->parent : 0;

		$form->add( new \IPS\Helpers\Form\Radio(
			            'dash_type',
			            NULL,
			            FALSE,
			            array( 'options'      => array( 'builder' => 'dash_type_builder', 'html' => 'dash_type_manual' ),
			                   'descriptions' => array( 'builder' => 'dash_type_builder_desc', 'html' => 'dash_type_manual_custom_desc' ) ),
			            NULL,
			            NULL,
			            NULL,
			            'dash_type'
		            ) );


		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=dashes&do=form&subnode=1&dash_type=' . $values['dash_type'] . '&parent=' . \IPS\Request::i()->parent ) );
		}

		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( \IPS\Member::loggedIn()->language()->addToStack('content_add_dash'), $form, FALSE );
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('content_add_dash');
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		if ( isset( \IPS\Request::i()->id ) )
		{
			\IPS\dcudash\Dashes\Dash::deleteCompiled( \IPS\Request::i()->id );
		}

		return parent::delete();
	}

	/**
	 * Set as default dash for this folder
	 *
	 * @return void
	 */
	protected function setAsDefault()
	{
		\IPS\dcudash\Dashes\Dash::load( \IPS\Request::i()->id )->setAsDefault();
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dashes" ), 'saved' );
	}

	/**
	 * Tree Search
	 *
	 * @return	void
	 */
	protected function search()
	{
		$rows = array();
		$url  = \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dashes" );

		/* Get results */
		$folders = \IPS\dcudash\Dashes\Folder::search( 'folder_name'  , \IPS\Request::i()->input, 'folder_name' );
		$dashes   = \IPS\dcudash\Dashes\Dash::search( 'dash_seo_name', \IPS\Request::i()->input, 'dash_seo_name' );

		$results =  \IPS\dcudash\Dashes\Folder::munge( $folders, $dashes );

		/* Convert to HTML */
		foreach ( $results as $id => $result )
		{
			$rows[ $id ] = ( $result instanceof \IPS\dcudash\Dashes\Dash ) ? $this->getDashRow( $result, $url ) : $this->getFolderRow( $result, $url );
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}

	/**
	 * Return HTML for a dash row
	 *
	 * @param   array   $row	Row data
	 * @param	object	$url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getDashRow( $dash, $url )
	{
		$badge = NULL;
		
		if ( isset( static::$dashToDatabaseMap[ $dash->id ] ) )
		{
			$badge = array( 0 => 'style7', 1 => \IPS\Member::loggedIn()->language()->addToStack( 'dash_database_display', NULL, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('content_db_' . static::$dashToDatabaseMap[ $dash->id ] ) ) ) ) );
		}
		return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $dash->id, $dash->seo_name, false, $dash->getButtons( \IPS\Http\url::internal('app=dcudash&module=dashes&controller=dashes'), true ), "", 'file-text-o', NULL, FALSE, NULL, NULL, $badge, FALSE, FALSE, FALSE );
	}

	/**
	 * Return HTML for a folder row
	 *
	 * @param   array   $row	Row data
	 * @param	object	$url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getFolderRow( $folder, $url )
	{
		return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $folder->id, $folder->name, true, $folder->getButtons( \IPS\Http\url::internal('app=dcudash&module=dashes&controller=dashes') ),  "", 'folder-o', NULL );
	}

	/**
	 * Fetch rows of folders/dashes
	 *
	 * @param int $folderId		Parent ID to fetch from
	 */
	public static function getRowsForTree( $folderId=0 )
	{
		try
		{
			if ( $folderId === 0 )
			{
				$folders = \IPS\dcudash\Dashes\Folder::roots();
			}
			else
			{
				$folders = \IPS\dcudash\Dashes\Folder::load( $folderId )->children( NULL, NULL, FALSE );
			}
		}
		catch( \OutOfRangeException $ex )
		{
			$folders = array();
		}

		$dashes   = \IPS\dcudash\Dashes\Dash::getChildren( $folderId );

		return \IPS\dcudash\Dashes\Folder::munge( $folders, $dashes );
	}

	/**
	 * Redirect after save
	 *
	 * @param	\IPS\Node\Model	$old			A clone of the node as it was before or NULL if this is a creation
	 * @param	\IPS\Node\Model	$new			The node now
	 * @param	string			$lastUsedTab	The tab last used in the form
	 * @return	void
	 */
	protected function _afterSave( \IPS\Node\Model $old = NULL, \IPS\Node\Model $new, $lastUsedTab = FALSE )
	{
		/* If this dash was the default in a folder, and it was moved to a new folder that already has a default, we need to unset the 
			default dash flag or there will be two defaults in the destination folder */
		if( $old !== NULL AND $old->folder_id != $new->folder_id AND $old->default )
		{
			/* Is there already a default dash in the new folder? */
			try
			{
				$existingDefault = \IPS\Db::i()->select( 'dash_id', 'dcudash_dashes', array( 'dash_folder_id=? and dash_default=?', $new->folder_id, 1 ) )->first();

				\IPS\Db::i()->update( 'dcudash_dashes', array( 'dash_default' => 0 ), array( 'dash_id=?', $new->id ) );

				\IPS\dcudash\Dashes\Dash::buildDashUrlStore();
			}
			catch( \UnderflowException $e )
			{
				/* No default found in destination folder, do nothing */
			}
		}
		
		/* If dash filename changes or the folder ID changes, we need to clear front navigation cache*/
		if( $old !== NULL AND ( $old->folder_id != $new->folder_id OR $old->seo_name != $new->seo_name ) )
		{
			unset( \IPS\Data\Store::i()->dashes_dash_urls );
		}

		parent::_afterSave( $old, $new, $lastUsedTab );
	}
}