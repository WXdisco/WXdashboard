<?php
/**
 * @brief		Background Task: Rebuild database editor fields
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database editor fields
 */
class _RebuildEditorFields
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= 50;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
		$fieldId    = $data['fieldId'];

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'MAX(primary_id_field)', 'dcudash_custom_database_' . $databaseId )->first();
		}
		catch( \Exception $ex )
		{
			throw new \OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}
		
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
		$fieldId    = $data['fieldId'];

		$parsed	= 0;
		$class  = '\IPS\dcudash\Records' . $databaseId;
		$last   = NULL;
		
		if ( \IPS\Db::i()->checkForTable( 'dcudash_custom_database_' . $databaseId ) AND \IPS\Db::i()->checkForColumn( 'dcudash_custom_database_' . $databaseId, 'field_' . $fieldId ) )
		{
			foreach ( \IPS\Db::i()->select( '*', 'dcudash_custom_database_' . $databaseId, array( 'primary_id_field > ?', $offset ), 'primary_id_field asc', array( 0, $this->rebuild ) ) as $row )
			{
				$item = $class::constructFromData( $row );
				$contentColumn = 'field_' . $fieldId;
				
				$member     = \IPS\Member::load( $item->mapped('author') );
				$extensions = \IPS\Application::load( $classname::$application )->extensions( 'core', 'EditorLocations' );
				$idColumn   = $classname::$databaseColumnId;
				
				if( isset( $classname::$itemClass ) )
				{
					$itemClass	= $classname::$itemClass;
					$module		= mb_ucfirst( $itemClass::$module );
				}
				else
				{
					$module     = mb_ucfirst( $classname::$module );
				}
				
				$extension  = NULL;
				
				if ( isset( $extensions[ $module ] ) )
				{
					$extension = $extensions[ $module ];
				}
				
				$canUseHtml = (bool) $member->group['g_dohtml'];
				
				if ( $extension )
				{
					$extensionCanUseHtml = $extension->canUseHtml( $member );
					if ( $extensionCanUseHtml !== NULL )
					{
						$canUseHtml = $extensionCanUseHtml;
					}
				}
			
				try
				{
					$item->$contentColumn	= \IPS\Text\LegacyParser::parseStatic( $item->$contentColumn, $member, $canUseHtml, 'dcudash_Records', $item->$idColumn, $data['fieldId'], $databaseId, isset( $classname::$itemClass ) ? $classname::$itemClass : get_class( $item ) );
				}
				catch( \InvalidArgumentException $e )
				{
					if( $e->getcode() == 103014 )
					{
						$item->$contentColumn	= preg_replace( "#\[/?([^\]]+?)\]#", '', $item->$contentColumn );
					}
					else
					{
						throw $e;
					}
				}
				
				$item->save();
			
				$last = $item->$idColumn;
			}
		}

		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $last;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
				
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_dcudash_database_records', FALSE, array( 'sprintf' => array( \IPS\dcudash\Databases::load( $databaseId )->_title ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}