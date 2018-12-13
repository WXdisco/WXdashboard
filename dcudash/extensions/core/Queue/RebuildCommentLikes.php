<?php
/**
 * @brief		Background Task: Rebuild comment likes
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
 * Background Task: Rebuild comment likes
 */
class _RebuildCommentLikes
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= 500;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_reputation_index', array( 'app=? AND type=?', 'dcudash', 'id' ) )->first();
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
	public function run( &$data, $offset )
	{
		$count = 0;

		foreach( \IPS\Db::i()->select( '*', 'core_reputation_index', array( '`app`=? AND `type`=?', 'dcudash', 'id' ), 'type_id ASC', array( 0, $this->rebuild ) ) as $item )
		{
			$count++;

			try
			{
				$databaseId = \IPS\Db::i()->select( 'comment_database_id', 'dcudash_database_comments', array( 'comment_id=?', intval( $item['id'] ) ) )->first();

				\IPS\Db::i()->update( 'core_reputation_index', array( 'type' => 'comment_id_' . $databaseId ), array( 'id=?', $item['id'] ) );
			}
			catch( \UnderflowException $e )
			{
				/* Comment no longer exists */
				\IPS\Db::i()->delete( 'core_reputation_index', array( '`app`=? and `type`=? and `id`=?', 'dcudash', 'id', $item['id'] ) );
			}
		}

		if( !$count )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		return ( $offset + $count );
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
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_comment_likes'), 'complete' => round( 100 / $data['count'] * $offset, 2 ) );
	}	
}