<?php
/**
 * @brief		Forum Post Model
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\Records;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 */
class _CommentTopicSync extends \IPS\dcudash\Records\Comment
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'pid';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'forums_posts';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = '';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'				=> 'topic_id',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'post',
		'date'				=> 'post_date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_show'			=> 'append_edit',
		'edit_member_name'	=> 'edit_name',
		'edit_reason'		=> 'post_edit_reason',
		'hidden'			=> 'queued',
		'first'				=> 'new_topic'
	);
	
	/**
	 * @brief	Application
	 */
	public static $application = 'forums';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'comment';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static $commentTemplate = array( array( 'display', 'dcudash', 'database' ), 'commentContainer' );
	
	/**
	 * @brief	Bitwise values for post_bwoptions field
	 */
	public static $bitOptions = array(
		'post_bwoptions' => array(
			'post_bwoptions' => array(
				'best_answer'	=> 1
			)
		)
	);

	/**
	 * Get containing item
	 *
	 * @return	\IPS\dcudash\Record
	 */
	public function item()
	{
		$itemClass = static::$itemClass;
		$lookFor = 'IPS\dcudash\Records\RecordsTopicSync';

		if ( mb_substr( $itemClass, 0, mb_strlen( $lookFor ) ) === $lookFor )
		{
			$id = $this->topic_id;
		}
		else
		{
			$id = \IPS\Request::i()->id;
		}
		
		return $itemClass::load( $id );
	}
	
	/**
	 * Delete Comment
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* We need the classname to be \IPS\forums\Topic\Post so topic/forum sync occur and to ensure post is removed from search index */
		$comment = \IPS\forums\Topic\Post::load( $this->pid );
		$comment->delete();
	}
	
	/**
	 * Edit Comment Contents - Note: does not add edit log
	 *
	 * @param	string	$newContent	New content
	 * @return	string|NULL
	 */
	public function editContents( $newContent )
	{
		$comment = \IPS\forums\Topic\Post::load( $this->pid );
		$comment->editContents( $newContent );
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		if ( ! empty( $this->pid ) )
		{
			try
			{
				$comment = \IPS\forums\Topic\Post::load( $this->pid );
				$template = static::$commentTemplate[1];
				static::$commentTemplate[0][0] = $this->item()->database()->template_display;
				return call_user_func_array( array( \IPS\dcudash\Theme::i(), 'getTemplate' ), static::$commentTemplate[0] )->$template( $comment->item(), $comment );
			}
			catch( \OutOfRangeException $e )
			{
				return parent::html();
			}
		}
		else
		{
			return parent::html();
		}
	}
	
	/**
	 * Report
	 *
	 * @param	string	$reportContent	Report content message from member
	 * @param	int		$reportType		Report type (see constants in \IPS\core\Reports\Report
	 * @return	\\PS\core\Reports\Report
	 * @throws	\UnexpectedValueException	If there is a permission error - you should only call this method after checking canReport
	 */
	public function report( $reportContent, $reportType=1 )
	{
		$comment = \IPS\forums\Topic\Post::load( $this->pid );
		return $comment->report( $reportContent, $reportType );
	}
	
	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere()
	{
		return NULL;
	}

	/**
	 * Return custom where for SQL delete
	 *
	 * @param   int     $id     Content item to delete from
	 * @return array
	 */
	public static function deleteWhereSql( $id )
	{
		return array( array( static::$databasePrefix . static::$databaseColumnMap['item'] . '=?', $id ) );
	}

	/**
	 * Post count for member
	 *
	 * @param	\IPS\Member	$member	The memner
	 * @return	int
	 * @note	We explicitely return 0 because any posts will be counted via the forums application inherently already
	 */
	public static function memberPostCount( \IPS\Member $member )
	{
		return 0;
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType()
	{
		return 'pid';
	}
	
	/**
	 * Reaction class
	 *
	 * @return	string
	 */
	public static function reactionClass()
	{
		return 'IPS\forums\Topic\Post';
	}

}