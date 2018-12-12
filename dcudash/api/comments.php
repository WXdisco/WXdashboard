<?php
/**
 * @brief		Dashboard Application Class
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright   	(c) 2018 devCU Software
 * @contact         gary@devcu.com
 * @site            https://www.devcu.com
 * @Source          https://github.com/WXdisco/wxdashboard  
 * @subpackage  	Dashboard Content
 * @since		12 DEC 2018
 * @version		1.0
 */

namespace IPS\dcudash\api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboards Database Comments API
 */
class _comments extends \IPS\Content\Api\CommentController
{
	/**
	 * Class
	 */
	protected $class = NULL;
	
	/**
	 * Get endpoint data
	 *
	 * @param	array	$pathBits	The parts to the path called
	 * @return	array
	 * @throws	\RuntimeException
	 */
	protected function _getEndpoint( $pathBits )
	{
		if ( !count( $pathBits ) )
		{
			throw new \RuntimeException;
		}
		
		$database = array_shift( $pathBits );
		if ( !count( $pathBits ) )
		{
			return array( 'endpoint' => 'index', 'params' => array( $database ) );
		}
		
		$nextBit = array_shift( $pathBits );
		if ( intval( $nextBit ) != 0 )
		{
			if ( count( $pathBits ) )
			{
				return array( 'endpoint' => 'item_' . array_shift( $pathBits ), 'params' => array( $database, $nextBit ) );
			}
			else
			{				
				return array( 'endpoint' => 'item', 'params' => array( $database, $nextBit ) );
			}
		}
				
		throw new \RuntimeException;
	}
	
	/**
	 * GET /dcudash/comments/{database_id}
	 * Get list of comments
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only comments the authorized user can view will be included
	 * @param		int		$database			Database ID
	 * @apiparam	string	categories			Comma-delimited list of category IDs
	 * @apiparam	string	authors				Comma-delimited list of member IDs - if provided, only topics started by those members are returned
	 * @apiparam	int		locked				If 1, only comments from events which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden				If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured			If 1, only comments from  events which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy				What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir				Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		dash				Dash number
	 * @apiparam	int		perDash				Number of results per dash - defaults to 25
	 * @throws		2T311/1	INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\Api\PaginatedResponse<IPS\dcudash\Records\Comment>
	 */
	public function GETindex( $database )
	{
		/* Load database */
		try
		{
			$database = \IPS\dcudash\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			$this->class = 'IPS\dcudash\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/1', 404 );
		}	
		
		/* Return */
		return $this->_list( array( array( 'comment_database_id=?', $database->id ) ), 'categories' );
	}
	
	/**
	 * GET /dcudash/comments/{database_id}/{id}
	 * View information about a specific comment
	 *
	 * @param		int		$database			Database ID
	 * @param		int		$comment			Comment ID
	 * @throws		2T311/1	INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/3	INVALID_ID	The comment ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\dcudash\Records\Comment
	 */
	public function GETitem( $database, $comment )
	{
		/* Load database */
		try
		{
			$database = \IPS\dcudash\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/2', 404 );
		}	
		
		/* Return */
		try
		{
			$class = 'IPS\dcudash\Records\Comment' . $database->id;
			if ( $this->member )
			{
				$object = $class::loadAndCheckPerms( $comment, $this->member );
			}
			else
			{
				$object = $class::load( $comment );
			}
			
			return new \IPS\Api\Response( 200, $object->apiOutput( $this->member ) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/3', 404 );
		}
	}
	
	/**
	 * POST /dcudash/comments/{database_id}
	 * Create a comment
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authentictaed user has permission to hide content).
	 * @param		int			$database			Database ID
	 * @reqapiparam	int			record				The ID number of the record the comment is for
	 * @reqapiparam	int			author				The ID number of the member making the comment (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @reqapiparam	string		content				The comment content as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	datetime	date				The date/time that should be used for the comment date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	string		ip_address			The IP address that should be stored for the comment. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	int			hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @throws		2T311/4		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/5		INVALID_ID			The comment ID does not exist
	 * @throws		1T311/6		NO_AUTHOR			The author ID does not exist
	 * @throws		1T311/7		NO_CONTENT			No content was supplied
	 * @throws		2T311/D		NO_PERMISSION		The authorized user does not have permission to comment on that record
	 * @return		\IPS\dcudash\Records\Comment
	 */
	public function POSTindex( $database )
	{
		/* Load database */
		try
		{
			$database = \IPS\dcudash\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			$this->class = 'IPS\dcudash\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/4', 404 );
		}	
		
		/* Get record */
		try
		{
			$recordClass = 'IPS\dcudash\Records' . $database->id;
			$record = $recordClass::load( \IPS\Request::i()->record );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/5', 403 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$record->canComment( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T311/D', 403 );
			}
			$author = $this->member;
		}
		else
		{
			if ( \IPS\Request::i()->author )
			{
				$author = \IPS\Member::load( \IPS\Request::i()->author );
				if ( !$author->member_id )
				{
					throw new \IPS\Api\Exception( 'NO_AUTHOR', '1T311/6', 404 );
				}
			}
			else
			{
				$author = new \IPS\Member;
				$author->name = \IPS\Request::i()->author_name;
			}
		}
		
		/* Check we have a post */
		if ( !\IPS\Request::i()->content )
		{
			throw new \IPS\Api\Exception( 'NO_CONTENT', '1T311/7', 403 );
		}
		
		/* Do it */
		return $this->_create( $record, $author );
	}
	
	/**
	 * POST /dcudash/comments/{database_id}/{comment_id}
	 * Edit a comment
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authentictaed user has permission to hide content).
	 * @param		int			$database		Database ID
	 * @param		int			$comment		Comment ID
	 * @apiparam	int			author			The ID number of the member making the comment (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string		author_name		If author is 0, the guest name that should be used
	 * @apiparam	string		content			The comment content as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	int			hidden				1/0 indicating if the topic should be hidden
	 * @throws		2T311/7		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/8		INVALID_ID			The comment ID does not exist or the authorized user does not have permission to view it
	 * @throws		1T311/9		NO_AUTHOR			The author ID does not exist
	 * @throws		2T311/E		NO_PERMISSION		The authorized user does not have permission to edit the comment
	 * @return		\IPS\dcudash\Records\Comment
	 */
	public function POSTitem( $database, $comment )
	{
		/* Load database */
		try
		{
			$database = \IPS\dcudash\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			$this->class = 'IPS\dcudash\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/7', 404 );
		}	
		
		/* Do it */
		try
		{
			/* Load */
			$comment = call_user_func( array( $this->class, 'load' ), $comment );
			if ( $this->member and !$comment->canView( $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			if ( $this->member and !$comment->canEdit( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T311/E', 403 );
			}
						
			/* Do it */
			try
			{
				return $this->_edit( $comment );
			}
			catch ( \InvalidArgumentException $e )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1T311/9', 400 );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/8', 404 );
		}
	}
		
	/**
	 * DELETE /dcudash/comments/{database_id}/{comment_id}
	 * Deletes a comment
	 *
	 * @param		int			$database			Database ID
	 * @param		int			$comment			Comment ID
	 * @throws		2T311/A		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/B		INVALID_ID			The comment ID does not exist
	 * @throws		2T311/F		NO_PERMISSION		The authorized user does not have permission to delete the comment
	 * @return		void
	 */
	public function DELETEitem( $database, $comment )
	{
		/* Load database */
		try
		{
			$database = \IPS\dcudash\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			$this->class = 'IPS\dcudash\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/A', 404 );
		}	
		
		/* Do it */
		try
		{			
			$class = $this->class;
			$object = $class::load( $comment );
			if ( $this->member and !$object->canDelete( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T311/F', 403 );
			}
			$object->delete();
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/B', 404 );
		}
	}
}