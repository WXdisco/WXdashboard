<?php
/**
 * @brief		Post Model
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
 * Post Model
 */
class _Review extends \IPS\Content\Review implements \IPS\Content\EditHistory, \IPS\Content\Hideable, \IPS\Content\Shareable, \IPS\Content\Searchable, \IPS\Content\Embeddable
{
	use \IPS\Content\Reactable, \IPS\Content\Reportable;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static $itemClass = NULL;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'dcudash_database_reviews';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'review_';
	
	/**
	 * @brief	Application
	 */
	public static $application = 'dcudash';

	/**
	 * @brief	Title
	 */
	public static $title = 'content_review';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'				=> 'item',
		'author'			=> 'author',
		'author_name'		=> 'author_name',
		'content'			=> 'content',
		'date'				=> 'date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_member_name'	=> 'edit_member_name',
		'edit_show'			=> 'edit_show',
		'rating'			=> 'rating',
		'votes_total'		=> 'votes_total',
		'votes_helpful'		=> 'votes_helpful',
		'votes_data'		=> 'votes_data',
		'approved'			=> 'approved',
		'author_response'	=> 'author_response',
	);
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'comment';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static $commentTemplate = array( array( 'display', 'dcudash', 'database' ), 'reviewContainer' );
	
	/**
	 * @brief	Database ID
	 */
	public static $customDatabaseId = NULL;
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'dcud-records-review';

	/**
	 * Create first comment (created with content item)
	 *
	 * @param	\IPS\Content\Item		$item			The content item just created
	 * @param	string					$comment		The comment
	 * @param	bool					$first			Is the first comment?
	 * @param	int						$rating			The rating (1-5)
	 * @param	string					$guestName		If author is a guest, the name to use
	 * @param	\IPS\Member|NULL		$member			The author of this comment. If NULL, uses currently logged in member.
	 * @param	\IPS\DateTime|NULL		$time			The time
	 * @param	string|NULL				$ipAddress		The IP address or NULL to detect automatically
	 * @param	int|NULL				$hiddenStatus		NULL to set automatically or override: 0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @return	static
	 * @throws	\InvalidArgumentException
	 */
	public static function create( $item, $comment, $first=FALSE, $rating=NULL, $guestName=NULL, $member=NULL, \IPS\DateTime $time=NULL, $ipAddress=NULL, $hiddenStatus=NULL )
	{
		$review = call_user_func_array( 'parent::create', func_get_args() );

		$review->database_id = static::$customDatabaseId;
		$review->save();
		
		return $review;
	}

	/**
	 * Return custom where for SQL delete
	 *
	 * @param   int     $id     Content item to delete from
	 * @return array
	 */
	public static function deleteWhereSql( $id )
	{
		return array( array( static::$databasePrefix . static::$databaseColumnMap['item'] . '=?', $id ), array( static::$databasePrefix . 'database_id=?', static::$customDatabaseId ) );
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$template = static::$commentTemplate[1];
		return call_user_func_array( array( \IPS\dcudash\Theme::i(), 'getTemplate' ), static::$commentTemplate[0] )->$template( $this->item(), $this );
	}
	
	/**
	 * Get URL for doing stuff
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$url = parent::url( $action );

		if ( $action !== NULL )
		{
			$url = $url->setQueryString( 'd', static::$customDatabaseId );
		}
		
		return $url;
	}
	
	/**
	 * Get attachment IDs
	 *
	 * @return	array
	 */
	public function attachmentIds()
	{
		$item = $this->item();
		$idColumn = $item::$databaseColumnId;
		$commentIdColumn = static::$databaseColumnId;
		return array( $this->item()->$idColumn, $this->$commentIdColumn, static::$customDatabaseId . '-review' ); 
	}

	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere()
	{
		return array( 'review_database_id=?', static::$customDatabaseId );
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 * @todo This was implemented improperly before - look into upgrade routine to fix
	 */
	public static function reactionType()
	{
		$databaseId = static::$customDatabaseId;
		return "review_id_{$databaseId}";
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( $params )
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'embed.css', 'dcudash', 'front' ) );
		return \IPS\Theme::i()->getTemplate( 'global', 'dcudash' )->embedRecordReview( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}
}