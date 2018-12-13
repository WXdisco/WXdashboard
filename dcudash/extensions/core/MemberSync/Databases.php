<?php
/**
 * @brief		Member Sync
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\MemberSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Sync
 */
class _Databases
{
	/**
	 * Member is merged with another member
	 *
	 * @param	\IPS\Member	$member		Member being kept
	 * @param	\IPS\Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( $member, $member2 )
	{
		\IPS\Db::i()->update( 'dcudash_database_revisions', array( 'revision_member_id' => $member->member_id ), array( 'revision_member_id=?', $member2->member_id ) );

		try
		{
			foreach ( \IPS\Db::i()->select( 'database_id', 'dcudash_databases' ) as $id )
			{
				\IPS\Db::i()->update( 'dcudash_custom_database_' . $id, array( 'record_last_comment_by' => $member->member_id, 'record_last_comment_name' => $member->name ), array( 'record_last_comment_by=?', $member2->member_id ) );
				\IPS\Db::i()->update( 'dcudash_custom_database_' . $id, array( 'record_last_review_by' => $member->member_id, 'record_last_review_name' => $member->name ), array( 'record_last_review_by=?', $member2->member_id ) );
				\IPS\Db::i()->update( 'dcudash_custom_database_' . $id, array( 'record_edit_member_id' => $member->member_id, 'record_edit_member_name' => $member->name ), array( 'record_edit_member_id=?', $member2->member_id ) );
			}
		}
		catch ( \Exception $e ) {} // If you have not upgraded dashes but it is installed, this throws an error

		/* Update member fields in the db */
		foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields', array( 'field_type=?', 'Member' ) ) as $field )
		{
			$where = array(
				\IPS\Db::i()->findInSet( 'field_' . $field['field_id'], array( $member2->member_id ) )
			);

			foreach ( \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $field['field_database_id'], $where ) as $record )
			{
				$members = explode( ',', $record['field_' . $field['field_id'] ] );

				foreach( $members as $k => $_member )
				{
					if( $_member == $member2->member_id )
					{
						$members[ $k ] = $member->member_id;
					}
				}

				\IPS\Db::i()->update( 'dcudash_custom_database_' . $field['field_database_id'], array( 'field_' . $field['field_id'] => implode( ',', $members ) ), array( 'primary_id_field=?', $record['primary_id_field'] ) );
			}
		}
	}
	
	/**
	 * Member is deleted
	 *
	 * @param	$member	\IPS\Member	The member
	 * @return	void
	 */
	public function onDelete( $member )
	{
		\IPS\Db::i()->update( 'dcudash_database_revisions', array( 'revision_member_id' => 0 ), array( 'revision_member_id=?', $member->member_id ) );

		try
		{
			foreach ( \IPS\Db::i()->select( 'database_id', 'dcudash_databases' ) as $id )
			{
				\IPS\Db::i()->update( 'dcudash_custom_database_' . $id, array( 'record_last_comment_by' => 0 ), array( 'record_last_comment_by=?', $member->member_id ) );
				\IPS\Db::i()->update( 'dcudash_custom_database_' . $id, array( 'record_last_review_by' => 0 ), array( 'record_last_review_by=?', $member->member_id ) );
				\IPS\Db::i()->update( 'dcudash_custom_database_' . $id, array( 'record_edit_member_id' => 0 ), array( 'record_edit_member_id=?', $member->member_id ) );
			}
		}
		catch ( \Exception $e ) {} // If you have not upgraded dashes but it is installed, this throws an error

		/* Update member fields in the db */
		try
		{
			foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields', array( 'field_type=?', 'Member' ) ) as $field )
			{
				$where = array(
					\IPS\Db::i()->findInSet( 'field_' . $field['field_id'], array( $member->member_id ) )
				);
	
				foreach ( \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $field['field_database_id'], $where ) as $record )
				{
					$members = explode( ',', $record['field_' . $field['field_id'] ] );
	
					foreach( $members as $k => $_member )
					{
						if( $_member == $member->member_id )
						{
							unset( $members[ $k ] );
						}
					}
	
					\IPS\Db::i()->update( 'dcudash_custom_database_' . $field['field_database_id'], array( 'field_' . $field['field_id'] => implode( ',', $members ) ), array( 'primary_id_field=?', $record['primary_id_field'] ) );
				}
			}
		}
		catch ( \Exception $e ) {} // If you have not upgraded dashes but it is installed, this throws an error
	}
}