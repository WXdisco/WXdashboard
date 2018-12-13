<?php
/**
 * @brief		File Storage Extension: Records
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Records
 */
class _Records
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		$count = 0;
		
		$fields = array();
		foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields', array( 'LOWER(field_type)=\'upload\'' ) ) as $field )
		{
			$fields[ $field['field_database_id'] ][] = $field['field_id'];
		}
		
		foreach( \IPS\dcudash\Databases::databases() as $id => $db )
		{
			$where = array( 'record_image IS NOT NULL' );
			
			if ( isset( $fields[ $id ] ) )
			{
				foreach( $fields[ $id ] as $field_id )
				{
					$where[] = 'field_' . $field_id . ' IS NOT NULL';
				}
			}
			
			$count += \IPS\Db::i()->select( 'COUNT(*)', 'dcudash_custom_database_' . $id, implode( ' OR ', $where ) )->first();
		}
		
		return $count;
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		if ( ! isset( \IPS\Data\Store::i()->dcudash_files_move_data ) )
		{
			foreach( \IPS\dcudash\Databases::databases() as $id => $database )
			{
				$data[ $id ] = 0;
			}
			
			\IPS\Data\Store::i()->dcudash_files_move_data = $data;
		}
		
		$data = \IPS\Data\Store::i()->dcudash_files_move_data;
		
		if ( ! count( $data ) )
		{
			/* all done */
			unset( \IPS\Data\Store::i()->dcudash_files_move_data );
			
			throw new \UnderflowException;
		}
		
		reset( $data );
		$databaseId = key( $data );
		$realOffset = $data[ $databaseId ];
		
		$done   = 0;
		$fields = array( 'record_image', 'record_image_thumb' );
		foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields', array( 'field_database_id=? AND LOWER(field_type)=\'upload\'', $databaseId ) ) as $field )
		{
			$fields[] = 'field_' . $field['field_id'];
		}
		
		$conditions = array();
		
		foreach( $fields as $field )
		{
			$conditions[] = $field . ' IS NOT NULL';
		}
		
		$where = array( implode( ' OR ', $conditions ) );

		foreach( \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $databaseId, $where, 'primary_id_field ASC', array( $realOffset, 1 ) ) as $row )
		{
			foreach( $fields as $field )
			{
				if ( isset( $row[ $field ] ) and ! empty( $row[ $field ] ) )
				{
					if ( mb_strstr( $row[ $field ], ',' ) )
					{
						$files = explode( ',', $row[ $field ] );
					}
					else
					{
						$files = array( $row[ $field ] );
					}
					
					$finished = array();
					$save     = FALSE;
					
					foreach( $files as $file )
					{
						try
						{
							$check = \IPS\File::get( $oldConfiguration ?: 'dcudash_Records', $file )->move( $storageConfiguration );
							
							if ( (string) $file != $check )
							{
								$fixed  = TRUE;
								$save[] = $check;
							}
							else
							{
								$save[]  = $file;
							}
						}
						catch( \Exception $e )
						{
							$save[] = $file;
						}
					}
					
					if ( $fixed )
					{
						\IPS\Db::i()->update( 'dcudash_custom_database_' . $databaseId, array( $field => implode( ',', $save ) ), array( 'primary_id_field=?', $row['primary_id_field'] ) );
					}
				}
			}
			
			$done++;
		}
		
		if ( $done )
		{
			$data[ $databaseId ] = $realOffset + 1;
		}
		else
		{
			/* Assume all done */
			unset( $data[ $databaseId ] );
		}
		
		if ( ! count( $data ) )
		{
			if ( isset( \IPS\Data\Store::i()->dcudash_files_move_data ) )
			{
				unset( \IPS\Data\Store::i()->dcudash_files_move_data );
			}
			
			throw new \Underflowexception;
		}
		else
		{	
			\IPS\Data\Store::i()->dcudash_files_move_data = $data;
		}
	}
	
	/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		if ( ! isset( \IPS\Data\Store::i()->dcudash_files_repair_data ) )
		{
			foreach( \IPS\dcudash\Databases::databases() as $id => $database )
			{
				$data[ $id ] = 0;
			}
			
			\IPS\Data\Store::i()->dcudash_files_repair_data = $data;
		}
		
		$data = \IPS\Data\Store::i()->dcudash_files_repair_data;
		
		if ( ! count( $data ) )
		{
			/* all done */
			unset( \IPS\Data\Store::i()->dcudash_files_repair_data );
			
			throw new \UnderflowException;
		}
		
		reset( $data );
		$databaseId = key( $data );
		$realOffset = $data[ $databaseId ];
		
		$done   = 0;
		$fields = array( 'record_image', 'record_image_thumb' );
		foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields', array( 'field_database_id=? AND LOWER(field_type)=\'upload\'', $databaseId ) ) as $field )
		{
			$fields[] = 'field_' . $field['field_id'];
		}
		
		$conditions = array();
		
		foreach( $fields as $field )
		{
			$conditions[] = $field . ' IS NOT NULL';
		}
		
		$where = array( implode( ' OR ', $conditions ) );

		foreach( \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $databaseId, $where, 'primary_id_field ASC', array( $realOffset, 1 ) ) as $row )
		{
			foreach( $fields as $field )
			{
				if ( isset( $row[ $field ] ) and ! empty( $row[ $field ] ) )
				{
					if ( mb_strstr( $row[ $field ], ',' ) )
					{
						$files = explode( ',', $row[ $field ] );
					}
					else
					{
						$files = array( $row[ $field ] );
					}
					
					$finished = array();
					$save     = FALSE;
					
					foreach( $files as $file )
					{
						try
						{
							if ( $new = \IPS\File::repairUrl( $file ) )
							{
								$fixed  = TRUE;
								$save[] = $new;
							}
							else
							{
								$save[]  = $file;
							}
						}
						catch( \Exception $e )
						{
							$save[] = $file;
						}
					}
					
					if ( $fixed )
					{
						\IPS\Db::i()->update( 'dcudash_custom_database_' . $databaseId, array( $field => implode( ',', $save ) ), array( 'primary_id_field=?', $row['primary_id_field'] ) );
					}
				}
			}
			
			$done++;
		}
		
		if ( $done )
		{
			$data[ $databaseId ] = $realOffset + 1;
		}
		else
		{
			/* Assume all done */
			unset( $data[ $databaseId ] );
		}
		 	
		if ( ! count( $data ) )
		{
			if ( isset( \IPS\Data\Store::i()->dcudash_files_repair_data ) )
			{
				unset( \IPS\Data\Store::i()->dcudash_files_repair_data );
			}
			
			throw new \Underflowexception;
		}
		else
		{	
			\IPS\Data\Store::i()->dcudash_files_repair_data = $data;
		}
	}
	
	/**
	 * Check if a file is valid
	 *
	 * @param	string	$file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( $file )
	{
		try
		{
			foreach( \IPS\dcudash\Databases::databases() as $id => $db )
			{
				$theFile = \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $id, array( 'record_image=? OR record_image_thumb=?', (string) $file, (string) $file ) )->first();

				return TRUE;
			}
		}
		catch ( \UnderflowException $e )
		{
			/* Gather all the upload fields */
			$fields = array();
			foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields', array( 'LOWER(field_type)=\'upload\'' ) ) as $field )
			{
				$fields[ $field['field_database_id'] ][] = $field['field_id'];
			}
			
			if ( is_array( $fields ) )
			{
				foreach( $fields as $databaseId => $data )
				{
					foreach( $data as $fieldId )
					{
						try
						{
							$record	= \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $databaseId, 'field_' . $fieldId . " LIKE '%" . (string) $file . "%'" )->first();
				
							return TRUE;
						}
						catch ( \UnderflowException $e )
						{
							/* Might be in another field */
						}
	
					}
				}
			}
		}
		
		return FALSE;
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( \IPS\dcudash\Databases::databases() as $databaseId => $database )
		{
			$fields = array( 'record_image', 'record_image_thumb' );
			foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields', array( 'field_database_id=? AND LOWER(field_type)=\'upload\'', $databaseId ) ) as $field )
			{
				$fields[] = 'field_' . $field['field_id'];
			}
			
			$where = array();
			
			foreach( $fields as $field )
			{
				$where[] = array( $field . ' IS NOT NULL' );
			}
	
			foreach( \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $databaseId, $where, 'primary_id_field ASC' ) as $row )
			{
				foreach( $fields as $field )
				{
					if ( isset( $row[ $field ] ) and ! empty( $row[ $field ] ) )
					{
						$file = \IPS\File::get( 'dcudash_Records', $row[ $field ] )->delete();
					}
				}
			}
		}
	}
}