<?php
/**
 * @brief		Background Task: Rebuild database categories
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
 * Background Task: Rebuild database categories
 */
class _RebuildCategories
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
		$databaseId = mb_substr( $classname, 18 );
		
		\IPS\Log::debug( "Getting preQueueData for " . $classname, 'rebuildCategories' );

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'COUNT(*)', 'dcudash_database_categories' )->first();
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
		$databaseId = mb_substr( $classname, 18 );
		
		$class  = '\IPS\dcudash\Categories' . $databaseId;
		$parsed	= 0;
		
		foreach ( \IPS\Db::i()->select( '*', 'dcudash_database_categories', array( 'category_database_id=?', $databaseId ), 'category_id asc', array( $offset, $this->rebuild ) ) as $row )
		{
			try
			{
				$cat = $class::constructFromData( $row );
				$cat->setLastComment();
				$cat->setLastReview();
				$cat->save();
				
				$parsed++;
			}
			catch( \Exception $e ){}
		}

		if( $parsed !== $this->rebuild )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return ( $offset + $this->rebuild );
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
		$databaseId = mb_substr( $classname, 18 );
		
		$title = ( \IPS\Application::appIsEnabled('dcudash') ) ? \IPS\dcudash\Databases::load( $databaseId )->_title : 'Database #' . $databaseId;
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack( 'rebuilding_dcudash_database_categories', FALSE, array( 'sprintf' => array( $title ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}