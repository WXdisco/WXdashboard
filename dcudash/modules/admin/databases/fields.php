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
 * fields
 */
class _fields extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\dcudash\Fields';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		$this->url = $this->url->setQueryString( array( 'database_id' => \IPS\Request::i()->database_id ) );
		
		$this->nodeClass = '\IPS\dcudash\Fields' . \IPS\Request::i()->database_id;
		
		\IPS\Dispatcher::i()->checkAcpPermission( 'dcudash_fields_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* If we lose the database id because of a log in, do something more useful than an uncaught exception */
		if ( ! isset( \IPS\Request::i()->database_id ) )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=databases" ) );
		}
		
		parent::manage();
		
		$url = \IPS\Http\Url::internal( "app=dcudash&module=databases&controller=fields&database_id=" . \IPS\Request::i()->database_id  );
		
		$class = '\IPS\dcudash\Fields' . \IPS\Request::i()->database_id;
		
		/* Build fixed fields */
		$fixed	= array_merge( array( 'record_publish_date' => array(), 'record_expiry_date' => array(), 'record_allow_comments' => array(), 'record_comment_cutoff' => array(), 'record_image' => array() ), $class::fixedFieldPermissions() );

		/* Fixed fields */
		$fixedFields = new \IPS\Helpers\Tree\Tree(
			$url,
			\IPS\Member::loggedIn()->language()->addToStack('content_fields_fixed_title'),
			function() use ( $fixed, $url )
			{
				$rows = array();
				
				foreach( $fixed as $field => $data )
				{
					$description = ( $field === 'record_publish_date' ) ? \IPS\Member::loggedIn()->language()->addToStack( 'content_fields_fixed_record_publish_date_desc' ) : NULL;
					$rows[ $field ] = \IPS\Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $field, \IPS\Member::loggedIn()->language()->addToStack( 'content_fields_fixed_'. $field ), FALSE, array(
						'permission'	=> array(
							'icon'		=> 'lock',
							'title'		=> 'permissions',
							'link'		=> $url->setQueryString( array( 'field' => $field, 'do' => 'fixedPermissions' ) ),
							'data'      => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack( 'content_fields_fixed_'. $field ) )
						)
					), $description, NULL, NULL, NULL, ( empty( $data['visible'] ) ? FALSE : TRUE )  );
				}
				
				return $rows;
			},
			function( $key, $root=FALSE ) use ( $fixed, $url ) {},
			function() { return 0; },
			function() { return array(); },
			function() { return array(); },
			FALSE,
			TRUE,
			TRUE
		);

		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'databases' )->fieldsWrapper( $fixedFields );

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('content_database_field_area', FALSE, array( 'sprintf' => array( \IPS\dcudash\Databases::load( \IPS\Request::i()->database_id)->_title ) ) );
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
	 * Fixed field permissions
	 *
	 * @return void
	 */
	public function enableToggle()
	{
		$class = '\IPS\dcudash\Fields' . \IPS\Request::i()->database_id;
		
		$class::setFixedFieldVisibility( \IPS\Request::i()->id, (boolean) \IPS\Request::i()->status );
		
		/* Redirect */
		if ( \IPS\Request::i()->status )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=databases&controller=fields&database_id=" . \IPS\Request::i()->database_id . '&do=fixedPermissions&field=' . \IPS\Request::i()->id ) );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=databases&controller=fields&database_id=" . \IPS\Request::i()->database_id ), 'saved' );
		}
	}

	/**
	 * Set this field as the record title
	 *
	 * @return void
	 */
	public function setAsTitle()
	{
		$class    = '\IPS\dcudash\Fields' . \IPS\Request::i()->database_id;
		$database = \IPS\dcudash\Databases::load( \IPS\Request::i()->database_id );

		try
		{
			$field = $class::load( \IPS\Request::i()->id );

			if ( $field->canBeTitleField() )
			{
				$database->field_title = $field->id;
				$database->save();
			}

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=databases&controller=fields&database_id=" . \IPS\Request::i()->database_id ), 'saved' );
		}
		catch( \OutOfRangeException $ex )
		{
			\IPS\Output::i()->error( 'dcudash_cannot_find_field', '2T255/1', 403, '' );
		}
	}

	/**
	 * Set this field as the record content
	 *
	 * @return void
	 */
	public function setAsContent()
	{
		$class    = '\IPS\dcudash\Fields' . \IPS\Request::i()->database_id;
		$database = \IPS\dcudash\Databases::load( \IPS\Request::i()->database_id );

		try
		{
			$field = $class::load( \IPS\Request::i()->id );

			if ( $field->canBeContentField() )
			{
				$database->field_content = $field->id;
				$database->save();
			}

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=databases&controller=fields&database_id=" . \IPS\Request::i()->database_id ), 'saved' );
		}
		catch( \OutOfRangeException $ex )
		{
			\IPS\Output::i()->error( 'dcudash_cannot_find_field', '2T255/2', 403, '' );
		}
	}

	/**
	 * Fixed field permissions
	 * 
	 * @return void
	 */
	public function fixedPermissions()
	{
		$class = '\IPS\dcudash\Fields' . \IPS\Request::i()->database_id;
		$perms = $class::fixedFieldPermissions( \IPS\Request::i()->field );

		$permMap = array( 'view' => 'view', 'edit' => 2, 'add' => 3 );

		foreach( $permMap as $k => $v )
		{
			if ( ! isset( $perms[ 'perm_' . $v ] ) )
			{
				$perms[ 'perm_' . $v ] = NULL;
			}
		}

		/* Build Matrix */
		$matrix = new \IPS\Helpers\Form\Matrix;
		$matrix->manageable = FALSE;
		$matrix->langPrefix = 'content_perm_fixed_fields__';
		$matrix->columns = array(
				'label'		=> function( $key, $value, $data )
				{
					return $value;
				},
		);
		foreach ( $permMap as $k => $v )
		{
			$matrix->columns[ $k ] = function( $key, $value, $data ) use ( $perms, $k, $v )
			{
				$groupId = mb_substr( $key, 0, -( 2 + mb_strlen( $k ) ) );
				return new \IPS\Helpers\Form\Checkbox( $key, isset( $perms[ "perm_{$v}" ] ) and ( $perms[ "perm_{$v}" ] === '*' or in_array( $groupId, explode( ',', $perms[ "perm_{$v}" ] ) ) ) );
			};
			$matrix->checkAlls[ $k ] = ( $perms[ "perm_{$v}" ] === '*' );
		}
		$matrix->checkAllRows = TRUE;
		
		$rows = array();
		foreach ( \IPS\Member\Group::groups() as $group )
		{
			$rows[ $group->g_id ] = array(
					'label'	=> $group->name,
					'view'	=> TRUE,
			);
		}
		$matrix->rows = $rows;
		
		/* Handle submissions */
		if ( $values = $matrix->values() )
		{
			$_perms = array();
			$save   = array();
				
			/* Check for "all" checkboxes */
			foreach ( $permMap as $k => $v )
			{
				if ( isset( \IPS\Request::i()->__all[ $k ] ) )
				{
					$_perms[ $v ] = '*';
				}
				else
				{
					$_perms[ $v ] = array();
				}
			}
				
			/* Loop groups */
			foreach ( $values as $group => $perms )
			{
				foreach ( $permMap as $k => $v )
				{
					if ( isset( $perms[ $k ] ) and $perms[ $k ] and is_array( $_perms[ $v ] ) )
					{
						$_perms[ $v ][] = $group;
					}
				}
			}
				
			/* Finalise */
			foreach ( $_perms as $k => $v )
			{
				$save[ "perm_{$k}" ] = is_array( $v ) ? implode( $v, ',' ) : $v;
			}
			
			$class::setFixedFieldPermissions( \IPS\Request::i()->field, $save );
			
			/* Redirect */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=dcudash&module=databases&controller=fields&database_id=" . \IPS\Request::i()->database_id ), 'saved' );
		}
		
		/* Display */
		\IPS\Output::i()->output .= $matrix;
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('content_database_manage_fields');
	
	}
}