<?php
/**
 * @brief		Records Model
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 */
class _Records extends \IPS\Content\Item implements
	\IPS\Content\Permissions,
	\IPS\Content\Pinnable, \IPS\Content\Lockable, \IPS\Content\Hideable, \IPS\Content\Featurable,
	\IPS\Content\Tags,
	\IPS\Content\Followable,
	\IPS\Content\Shareable,
	\IPS\Content\ReadMarkers,
	\IPS\Content\Views,
	\IPS\Content\Ratings,
	\IPS\Content\Searchable,
	\IPS\Content\FuturePublishing,
	\IPS\Content\Embeddable,
	\IPS\Content\MetaData
{
	use \IPS\Content\Reactable, \IPS\Content\Reportable;
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = NULL;
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'primary_id_field';

    /**
     * @brief	[ActiveRecord] Database ID Fields
     */
    protected static $databaseIdFields = array('record_static_furl', 'record_topicid');
    
    /**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();

	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = '';

	/**
	 * @brief	Application
	 */
	public static $application = 'dcudash';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'records';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = NULL;
	
	/**
	 * @brief	Node Class
	 */
	public static $containerNodeClass = NULL;
	
	/**
	 * @brief	[Content\Item]	Comment Class
	 */
	public static $commentClass = NULL;
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static $firstCommentRequired = FALSE;
	
	/**
	 * @brief	[Content\Item]	Form field label prefix
	 */
	public static $formLangPrefix = 'content_record_form_';
	
	/**
	 * @brief	[Records] Custom Database Id
	 */
	public static $customDatabaseId = NULL;
	
	/**
	 * @brief 	[Records] Database object
	 */
	protected static $database = array();
	
	/**
	 * @brief 	[Records] Database object
	 */
	public static $title = 'content_record_title';
		
	/**
	 * @brief	[Records] Standard fields
	 */
	protected static $standardFields = array( 'record_publish_date', 'record_expiry_date', 'record_allow_comments', 'record_comment_cutoff' );
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'dcud-records';

	/**
	 * @brief	Icon
	 */
	public static $icon = 'file-text';
	
	/**
	 * @brief	Include In Sitemap (We do not want to include in Content sitemap, as we have a custom extension
	 */
	public static $includeInSitemap = FALSE;
	
	/**
	 * @brief	Prevent custom fields being fetched twice when loading/saving a form
	 */
	public static $customFields = NULL;

	/**
	 * Whether or not to include in site search
	 *
	 * @return	bool
	 */
	public static function includeInSiteSearch()
	{
		return (bool) \IPS\dcudash\Databases::load( static::$customDatabaseId )->search;
	}

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );

		/* Prevent infinite redirects */
		if ( ! $obj->record_dynamic_furl and ! $obj->record_static_furl )
		{
			if ( $obj->_title )
			{
				$obj->record_dynamic_furl = \IPS\Http\Url\Friendly::seoTitle( mb_substr( $obj->_title, 0, 255 ) );
				$obj->save();
			}
		}

		if ( $obj->useForumComments() )
		{
			$obj::$commentClass = 'IPS\dcudash\Records\CommentTopicSync' . static::$customDatabaseId;
		}

		return $obj;
	}

	/**
	 * Set custom posts per dash setting
	 *
	 * @return int
	 */
	public static function getCommentsPerDash()
	{
		if ( ! empty( \IPS\dcudash\Databases\Dispatcher::i()->recordId ) )
		{
			$class = 'IPS\dcudash\Records' . static::$customDatabaseId;
			try
			{
				$record = $class::load( \IPS\dcudash\Databases\Dispatcher::i()->recordId );
				
				if ( $record->_forum_record and $record->_forum_comments and \IPS\Application::appIsEnabled('forums') )
				{
					return \IPS\forums\Topic::getCommentsPerDash();
				}
			}
			catch( \OutOfRangeException $e )
			{
				/* recordId is usually the record we're viewing, but this method is called on recordFeed widgets in horizontal mode which means recordId may not be __this__ record, so fail gracefully */
				return static::database()->field_perdash;
			}
		}
		else if( static::database()->forum_record and static::database()->forum_comments and \IPS\Application::appIsEnabled('forums') )
		{
			return \IPS\forums\Topic::getCommentsPerDash();
		}

		return static::database()->field_perdash;
	}

	/**
	 * Returns the database parent
	 * 
	 * @return \IPS\dcudash\Databases
	 */
	public static function database()
	{
		if ( ! isset( static::$database[ static::$customDatabaseId ] ) )
		{
			static::$database[ static::$customDatabaseId ] = \IPS\dcudash\Databases::load( static::$customDatabaseId );
		}
		
		return static::$database[ static::$customDatabaseId ];
	}
	
	/**
	 * Load record based on a URL
	 *
	 * @param	\IPS\Http\Url	$url	URL to load from
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function loadFromUrl( \IPS\Http\Url $url )
	{
		$qs = array_merge( $url->queryString, $url->hiddenQueryString );
		
		if ( isset( $qs['path'] ) )
		{
			$bits = explode( '/', trim( $qs['path'], '/' ) );
			$path = array_pop( $bits );
			
			try
			{
				return static::loadFromSlug( $path, FALSE );
			}
			catch ( \Exception $e ) { }
		}
		
		return parent::loadFromUrl( $url );
	}

	/**
	 * Load from slug
	 * 
	 * @param	string		$slug							Thing that lives in the garden and eats your plants
	 * @param	bool		$redirectIfSeoTitleIsIncorrect	If the SEO title is incorrect, this method may redirect... this stops that
	 * @param	integer		$categoryId						Optional category ID to restrict the look up in.
	 * @return	\IPS\dcudash\Record
	 */
	public static function loadFromSlug( $slug, $redirectIfSeoTitleIsIncorrect=TRUE, $categoryId=NULL )
	{
		$slug = trim( $slug, '/' );
		
		/* If the slug is an empty string, then there is nothing to try and load. */
		if ( empty( $slug ) )
		{
			throw new \OutOfRangeException;
		}

		/* Try the easiest option */
		preg_match( '#-r(\d+?)$#', $slug, $matches );

		if ( isset( $matches[1] ) AND is_numeric( $matches[1] ) )
		{
			try
			{
				$record = static::load( $matches[1] );

				/* Check to make sure the SEO title is correct */
				if ( $redirectIfSeoTitleIsIncorrect and urldecode( str_replace( $matches[0], '', $slug ) ) !== $record->record_dynamic_furl and !\IPS\Request::i()->isAjax() and mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and !\IPS\ENFORCE_ACCESS )
				{
					$url = $record->url();

					/* Don't correct the URL if the visitor cannot see the record */
					if( !$record->canView() )
					{
						throw new \OutOfRangeException;
					}

					/* Redirect to the embed form if necessary */
					if( isset( \IPS\Request::i()->do ) and \IPS\Request::i()->do == 'embed' )
					{
						$url = $url->setQueryString( array( 'do' => "embed" ) );
					}

					\IPS\Output::i()->redirect( $url );
				}

				static::$multitons[ $record->primary_id_field ] = $record;

				return static::$multitons[ $record->primary_id_field ];
			}
			catch( \OutOfRangeException $ex ) { }
		}

		$where = array( array( '? LIKE CONCAT( record_dynamic_furl, \'%\') OR record_static_furl=?', $slug, $slug ) );
		if ( $categoryId )
		{
			$where[] = array( 'category_id=?', $categoryId );
		}
		
		foreach( \IPS\Db::i()->select( '*', static::$databaseTable, $where ) as $record )
		{
			$pass = FALSE;
			
			if ( $slug === $record['record_static_furl'] )
			{
				$pass = TRUE;
			}
			else
			{
				if ( isset( $matches[1] ) AND is_numeric( $matches[1] ) AND $matches[1] == $record['primary_id_field'] )
				{
					$pass = TRUE;
				}
			}
				
			if ( $pass === TRUE )
			{
				static::$multitons[ $record['primary_id_field'] ] = static::constructFromData( $record );
			
				return static::$multitons[ $record['primary_id_field'] ];
			}	
		}
		
		/* Still here? Consistent with AR pattern */
		throw new \OutOfRangeException();	
	}

	/**
	 * Load from slug history so we can 301 to the correct record.
	 *
	 * @param	string		$slug	Thing that lives in the garden and eats your plants
	 * @return	\IPS\dcudash\Record
	 */
	public static function loadFromSlugHistory( $slug )
	{
		$slug = trim( $slug, '/' );

		try
		{
			$row = \IPS\Db::i()->select( '*', 'dcudash_url_store', array( 'store_type=? and store_path=?', 'record', $slug ) )->first();

			return static::load( $row['store_current_id'] );
		}
		catch( \UnderflowException $ex ) { }

		/* Still here? Consistent with AR pattern */
		throw new \OutOfRangeException();
	}

	/**
	 * Indefinite Dashboard
	 *
	 * @param	\IPS\Lang|NULL	$language	The language to use, or NULL for the language of the currently logged in member
	 * @return	string
	 */
	public function indefiniteDashboard( \IPS\Lang $lang = NULL )
	{
		$lang = $lang ?: \IPS\Member::loggedIn()->language();
		return $lang->addToStack( 'content_db_lang_ia_' . static::$customDatabaseId, FALSE );
	}
	
	/**
	 * Indefinite Dashboard
	 *
	 * @param	\IPS\Lang|NULL	$language	The language to use, or NULL for the language of the currently logged in member
	 * @return	string
	 */
	public static function _indefiniteDashboard( array $containerData = NULL, \IPS\Lang $lang = NULL )
	{
		$lang = $lang ?: \IPS\Member::loggedIn()->language();
		return $lang->addToStack( 'content_db_lang_ia_' . static::$customDatabaseId, FALSE );
	}
	
	/**
	 * Definite Dashboard
	 *
	 * @param	\IPS\Lang|NULL	$language	The language to use, or NULL for the language of the currently logged in member
	 * @return	string
	 */
	public function definiteDashboard( \IPS\Lang $lang = NULL )
	{
		$lang = $lang ?: \IPS\Member::loggedIn()->language();
		return $lang->addToStack( 'content_db_lang_sl_' . static::$customDatabaseId, FALSE );
	}
	
	/**
	 * Definite Dashboard
	 *
	 * @param	array			$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	\IPS\Lang|NULL	$language		The language to use, or NULL for the language of the currently logged in member
	 * @param	array			$options		Options to pass to \IPS\Lang::addToStack
	 * @return	string
	 */
	public static function _definiteDashboard( array $containerData = NULL, \IPS\Lang $lang = NULL, $options = array() )
	{
		$lang = $lang ?: \IPS\Member::loggedIn()->language();
		return $lang->addToStack( 'content_db_lang_sl_' . static::$customDatabaseId, FALSE, $options );
	}
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	\IPS\Node\Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL )
	{
		$customValues = ( $item ) ? $item->fieldValues() : array();
		$database     = \IPS\dcudash\Databases::load( static::$customDatabaseId );
		$fieldsClass  = 'IPS\dcudash\Fields' .  static::$customDatabaseId;
		$formElements = array();
		$elements     = parent::formElements( $item, $container );
		static::$customFields = $fieldsClass::fields( $customValues, ( $item ? 'edit' : 'add' ), $container, 0, ( ! $item ? NULL : $item ) );

		/* Build the topic state toggles */
		$options = array();
		$toggles = array();
		$values  = array();
		
		/* Title */
		if ( isset( static::$customFields[ $database->field_title ] ) )
		{
			$formElements['title'] = static::$customFields[ $database->field_title ];
		}

		if ( isset( $elements['guest_name'] ) )
		{
			$formElements['guest_name'] = $elements['guest_name'];
		}

		if ( isset( $elements['captcha'] ) )
		{
			$formElements['captcha'] = $elements['captcha'];
		}

		if ( \IPS\Member::loggedIn()->modPermission('can_content_edit_record_slugs') )
		{
			$formElements['record_static_furl_set'] = new \IPS\Helpers\Form\YesNo( 'record_static_furl_set', ( ( $item AND $item->record_static_furl ) ? TRUE : FALSE ), FALSE, array(
					'togglesOn' => array( 'record_static_furl' )
			)  );
			$formElements['record_static_furl'] = new \IPS\Helpers\Form\Text( 'record_static_furl', ( ( $item AND $item->record_static_furl ) ? $item->record_static_furl : NULL ), FALSE, array(), function( $val ) use ( $database )
            {
                /* Make sure key is unique */
                if ( empty( $val ) )
                {
                    return true;
                }
                
                /* Make sure it does not match the dynamic URL format */
                if ( preg_match( '#-r(\d+?)$#', $val ) )
                {
	                throw new \InvalidArgumentException('content_record_slug_not_unique');
                }

                try
                {
                    $cat = intval( ( isset( \IPS\Request::i()->content_record_form_container ) ) ? \IPS\Request::i()->content_record_form_container : 0 );
                    $recordsClass = '\IPS\dcudash\Records' . $database->id;

                    /* Fetch record by static slug */
                    $record = $recordsClass::load( $val, 'record_static_furl' );

                    /* In the same category though? */
                    if ( isset( \IPS\Request::i()->id ) and $record->_id == \IPS\Request::i()->id )
                    {
                        /* It's ok, it's us! */
                        return true;
                    }

                    if ( $cat === $record->category_id )
                    {
                        throw new \InvalidArgumentException('content_record_slug_not_unique');
                    }
                }
                catch ( \OutOfRangeException $e )
                {
                    /* Slug is OK as load failed */
                    return true;
                }

                return true;
            }, \IPS\Member::loggedIn()->language()->addToStack('record_static_url_prefix', FALSE, array( 'sprintf' => array( \IPS\Settings::i()->base_url ) ) ), NULL, 'record_static_furl' );
		}
		
		if ( isset( $elements['tags'] ) )
		{ 
			$formElements['tags'] = $elements['tags'];
		}

		/* Now custom fields */
		foreach( static::$customFields as $id => $obj )
		{
			if ( $database->field_title === $id )
			{
				continue;
			}

			$formElements['field_' . $id ] = $obj;

			if ( $database->field_content == $id )
			{
				if ( isset( $elements['auto_follow'] ) )
				{
					$formElements['auto_follow'] = $elements['auto_follow'];
				}

				if ( \IPS\Settings::i()->edit_log and $item )
				{
					if ( \IPS\Settings::i()->edit_log == 2 )
					{
						$formElements['record_edit_reason'] = new \IPS\Helpers\Form\Text( 'record_edit_reason', ( $item ) ? $item->record_edit_reason : NULL, FALSE, array( 'maxLength' => 255 ) );
					}
					if ( \IPS\Member::loggedIn()->group['g_append_edit'] )
					{
						$formElements['record_edit_show'] = new \IPS\Helpers\Form\Checkbox( 'record_edit_show', \IPS\Member::loggedIn()->member_id == $item->author()->member_id );
					}
				}
			}
		}

		$postKey = ( $item ) ? $item->_post_key : md5( mt_rand() );

		if ( $fieldsClass::fixedFieldFormShow( 'record_publish_date' ) AND \IPS\Member::loggedIn()->modPermission( "can_future_publish_content" ) )
		{
			$formElements['record_publish_date'] = $elements['date'];
		}

		if ( $fieldsClass::fixedFieldFormShow( 'record_image' ) )
		{
			$fixedFieldSettings = static::database()->fixed_field_settings;
			$dims = TRUE;

			if ( isset( $fixedFieldSettings['record_image']['image_dims'] ) )
			{
				$dims = array( 'maxWidth' => $fixedFieldSettings['record_image']['image_dims'][0], 'maxHeight' => $fixedFieldSettings['record_image']['image_dims'][1] );
			}

			$formElements['record_image'] = new \IPS\Helpers\Form\Upload( 'record_image', ( ( $item and $item->record_image ) ? \IPS\File::get( 'dcudash_Records', $item->record_image ) : NULL ), FALSE, array( 'image' => $dims, 'storageExtension' => 'dcudash_Records', 'postKey' => $postKey, 'multiple' => false ), NULL, NULL, NULL, 'record_image' );
		}

		if ( $fieldsClass::fixedFieldFormShow( 'record_expiry_date' ) )
		{
			$formElements['record_expiry_date'] = new \IPS\Helpers\Form\Date( 'record_expiry_date', ( ( $item AND $item->record_expiry_date ) ? \IPS\DateTime::ts( $item->record_expiry_date ) : NULL ), FALSE, array(
					'time'          => true,
					'unlimited'     => -1,
					'unlimitedLang' => 'record_datetime_noval'
			) );
		}

		if ( $fieldsClass::fixedFieldFormShow( 'record_allow_comments' ) )
		{
			$formElements['record_allow_comments'] = new \IPS\Helpers\Form\YesNo( 'record_allow_comments', ( ( $item ) ? $item->record_allow_comments : TRUE ), FALSE, array(
					'togglesOn' => array( 'record_comment_cutoff' )
			)  );
		}
		
		if ( $fieldsClass::fixedFieldFormShow( 'record_comment_cutoff' ) )
		{
			$formElements['record_comment_cutoff'] = new \IPS\Helpers\Form\Date( 'record_comment_cutoff', ( ( $item AND $item->record_comment_cutoff ) ? \IPS\DateTime::ts( $item->record_comment_cutoff ) : NULL ), FALSE, array(
					'time'          => true,
					'unlimited'     => -1,
					'unlimitedLang' => 'record_datetime_noval'
			), NULL, NULL, NULL, 'record_comment_cutoff' );
		}

		if ( static::modPermission( 'lock', NULL, $container ) )
		{
			$options['lock'] = 'create_record_locked';
			$toggles['lock'] = array( 'create_record_locked' );
			
			if ( $item AND $item->record_locked )
			{
				$values[] = 'lock';
			}
		}
			
		if ( static::modPermission( 'pin', NULL, $container ) )
		{
			$options['pin'] = 'create_record_pinned';
			$toggles['pin'] = array( 'create_record_pinned' );
			
			if ( $item AND $item->record_pinned )
			{
				$values[] = 'pin';
			}
		}
			
		if ( static::modPermission( 'hide', NULL, $container ) )
		{
			$options['hide'] = 'create_record_hidden';
			$toggles['hide'] = array( 'create_record_hidden' );
			
			if ( $item AND $item->record_approved === -1 )
			{
				$values[] = 'hide';
			}
		}
			
		if ( static::modPermission( 'feature', NULL, $container ) )
		{
			$options['feature'] = 'create_record_featured';
			$toggles['feature'] = array( 'create_record_featured' );

			if ( $item AND $item->record_featured === 1 )
			{
				$values[] = 'feature';
			}
		}
		
		if ( \IPS\Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			$formElements['record_meta_keywords'] = new \IPS\Helpers\Form\TextArea( 'record_meta_keywords', $item ? $item->record_meta_keywords : '', FALSE );
			$formElements['record_meta_description'] = new \IPS\Helpers\Form\TextArea( 'record_meta_description', $item ? $item->record_meta_description : '', FALSE );
		}
		
		if ( count( $options ) or count ( $toggles ) )
		{
			$formElements['create_record_state'] = new \IPS\Helpers\Form\CheckboxSet( 'create_record_state', $values, FALSE, array(
					'options' 	=> $options,
					'toggles'	=> $toggles,
					'multiple'	=> TRUE
			) );
		}

		return $formElements;
	}

	/**
	 * Total item count (including children)
	 *
	 * @param	\IPS\Node\Model	$container			The container
	 * @param	bool			$includeItems		If TRUE, items will be included (this should usually be true)
	 * @param	bool			$includeComments	If TRUE, comments will be included
	 * @param	bool			$includeReviews		If TRUE, reviews will be included
	 * @param	int				$depth				Used to keep track of current depth to avoid going too deep
	 * @return	int|NULL|string	When depth exceeds 10, will return "NULL" and initial call will return something like "100+"
	 * @note	This method may return something like "100+" if it has lots of children to avoid exahusting memory. It is intended only for display use
	 * @note	This method includes counts of hidden and unapproved content items as well
	 */
	public static function contentCount( \IPS\Node\Model $container, $includeItems=TRUE, $includeComments=FALSE, $includeReviews=FALSE, $depth=0 )
	{
		/* Are we in too deep? */
		if ( $depth > 10 )
		{
			return '+';
		}

		$count = $container->_items;

		if ( static::canViewHiddenItems( NULL, $container ) )
		{
			$count += $container->_unapprovedItems;
		}

		if ( static::canViewFutureItems( NULL, $container ) )
		{
			$count += $container->_futureItems;
		}

		if ( $includeComments )
		{
			$count += $container->record_comments;
		}

		/* Add Children */
		$childDepth	= $depth++;
		foreach ( $container->children() as $child )
		{
			$toAdd = static::contentCount( $child, $includeItems, $includeComments, $includeReviews, $childDepth );
			if ( is_string( $toAdd ) )
			{
				return $count . '+';
			}
			else
			{
				$count += $toAdd;
			}

		}
		return $count;
	}

	/**
	 * [brief] Display title
	 */
	protected $displayTitle = NULL;

	/**
	 * [brief] Display content
	 */
	protected $displayContent = NULL;

	/**
	 * [brief] Record dash
	 */
	protected $recordDash = NULL;

	/**
	 * [brief] Custom Display Fields
	 */
	protected $customDisplayFields = array();
	
	/**
	 * [brief] Custom Fields Database Values
	 */
	protected $customValueFields = NULL;
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( $values )
	{
		$fieldsClass  = 'IPS\dcudash\Fields' . static::$customDatabaseId;
		$database     = \IPS\dcudash\Databases::load( static::$customDatabaseId );

		/* Store a revision */
		if ( $database->revisions AND ! $this->_new )
		{
			$revision = new \IPS\dcudash\Records\Revisions;
			$revision->database_id = static::$customDatabaseId;
			$revision->record_id   = $this->_id;
			$revision->data        = $this->fieldValues( TRUE );

			$revision->save();
		}

		if ( isset( \IPS\Request::i()->postKey ) )
		{
			$this->post_key = \IPS\Request::i()->postKey;
		}

		if ( $this->_new )
		{
			$this->record_approved = ( static::moderateNewItems( \IPS\Member::loggedIn() ) ) ? 0 : 1;
		}

		/* Moderator actions */
		if ( isset( $values['create_record_state'] ) )
		{
			if ( in_array( 'lock', $values['create_record_state'] ) )
			{
				$this->record_locked = 1;
			}
			else
			{
				$this->record_locked = 0;
			}
	
			if ( in_array( 'hide', $values['create_record_state'] ) )
			{
				$this->record_approved = -1;
			}
			else if  ( $this->record_approved !== 0 )
			{
				$this->record_approved = 1;
			}
	
			if ( in_array( 'pin', $values['create_record_state'] ) )
			{
				$this->record_pinned = 1;
			}
			else
			{
				$this->record_pinned = 0;
			}
	
			if ( in_array( 'feature', $values['create_record_state'] ) )
			{
				$this->record_featured = 1;
			}
			else
			{
				$this->record_featured = 0;
			}
		}
	
		/* Dates */
		if ( isset( $values['record_expiry_date'] ) and $values['record_expiry_date'] )
		{
			if ( $values['record_expiry_date'] === -1 )
			{
				$this->record_expiry_date = 0;
			}
			else
			{
				$this->record_expiry_date = $values['record_expiry_date']->getTimestamp();
			}
		}
		if ( isset( $values['record_comment_cutoff'] ) and $values['record_comment_cutoff'] )
		{
			if ( $values['record_comment_cutoff'] === -1 )
			{
				$this->record_comment_cutoff = 0;
			}
			else
			{
				$this->record_comment_cutoff = $values['record_comment_cutoff']->getTimestamp();
			}
		}

		/* Edit stuff */
		if ( ! $this->_new )
		{
			if ( isset( $values['record_edit_reason'] ) )
			{
				$this->record_edit_reason = $values['record_edit_reason'];
			}

			$this->record_edit_time        = time();
			$this->record_edit_member_id   = \IPS\Member::loggedIn()->member_id;
			$this->record_edit_member_name = \IPS\Member::loggedIn()->name;

			if ( isset( $values['record_edit_show'] ) )
			{
				$this->record_edit_show = \IPS\Member::loggedIn()->group['g_append_edit'] ? $values['record_edit_show'] : TRUE;
			}
		}

		/* Record image */
		if ( array_key_exists( 'record_image', $values ) )
		{			
			if ( $values['record_image'] === NULL )
			{			
				if ( $this->record_image )
				{
					try
					{
						\IPS\File::get( 'dcudash_Records', $this->record_image )->delete();
					}
					catch ( \Exception $e ) { }
				}
				if ( $this->record_image_thumb )
				{
					try
					{
						\IPS\File::get( 'dcudash_Records', $this->record_image_thumb )->delete();
					}
					catch ( \Exception $e ) { }
				}
					
				$this->record_image = NULL;
				$this->record_image_thumb = NULL;
			}
			else
			{
				$fixedFieldSettings = static::database()->fixed_field_settings;

				if ( isset( $fixedFieldSettings['record_image']['thumb_dims'] ) )
				{
					if ( $this->record_image_thumb )
					{
						try
						{
							\IPS\File::get( 'dcudash_Records', $this->record_image_thumb )->delete();
						}
						catch ( \Exception $e ) { }
					}
					
					$thumb = $values['record_image']->thumbnail( 'dcudash_Records', $fixedFieldSettings['record_image']['thumb_dims'][0], $fixedFieldSettings['record_image']['thumb_dims'][1] );
				}
				else
				{
					$thumb = $values['record_image'];
				}

				$this->record_image       = (string)$values['record_image'];
				$this->record_image_thumb = (string)$thumb;
			}
		}
		
		/* Should we just lock this? */
		if ( ( isset( $values['record_allow_comments'] ) AND ! $values['record_allow_comments'] ) OR ( $this->record_comment_cutoff > $this->record_publish_date ) )
		{
			$this->record_locked = 1;
		}
		
		if ( \IPS\Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			foreach( array( 'record_meta_keywords', 'record_meta_description' ) as $k )
			{
				if ( isset( $values[ $k ] ) )
				{
					$this->$k = $values[ $k ];
				}
			}
		}

		/* Custom fields */
		$customValues = array();
		$afterEditNotificationsExclude = array();
	
		foreach( $values as $k => $v )
		{
			if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
			{
				$customValues[$k ] = $v;
			}
		}

		$categoryClass = 'IPS\dcudash\Categories' . static::$customDatabaseId;
		$container    = ( ! isset( $values['content_record_form_container'] ) ? $categoryClass::load( $this->category_id ) : $values['content_record_form_container'] );
		$fieldObjects = $fieldsClass::data( NULL, $container );
		
		if ( static::$customFields === NULL )
		{
			static::$customFields = $fieldsClass::fields( $customValues, ( $this->_new ? 'add' : 'edit' ), $container, 0, ( $this->_new ? NULL : $this ) );
		}
		
		foreach( static::$customFields as $key => $field )
		{
			$seen[] = $key;
			$key = 'field_' . $key;
			
			if ( !$this->_new )
			{
				$afterEditNotificationsExclude = array_merge_recursive( static::_getQuoteAndMentionIdsFromContent( $this->$key ) );
			}
			
			if ( isset( $customValues[ $field->name ] ) and get_class( $field ) == 'IPS\Helpers\Form\Upload' )
			{
				if ( is_array( $customValues[ $field->name ] ) )
				{
					$items = array();
					foreach( $customValues[ $field->name ] as $obj )
					{
						$items[] = (string) $obj;
					}
					$this->$key = implode( ',', $items );
				}
				else
				{
					$this->$key = (string) $customValues[ $field->name ];
				}
			}
			/* If we're using decimals, then the database field is set to DECIMALS, so we cannot using stringValue() */
			else if ( isset( $customValues[ $field->name ] ) and get_class( $field ) == 'IPS\Helpers\Form\Number' and ( isset( $field->options['decimals'] ) and $field->options['decimals'] > 0 ) )
			{
				$this->$key = $field->value;
			}
			else
			{
				$this->$key = $field::stringValue( isset( $customValues[ $field->name ] ) ? $customValues[ $field->name ] : NULL );
			}
		}

		/* Now set up defaults */
		if ( $this->_new )
		{
			foreach ( $fieldObjects as $obj )
			{
				if ( !in_array( $obj->id, $seen ) )
				{
					/* We've not got a value for this as the field is hidden from us, so let us add the default value here */
					$key        = 'field_' . $obj->id;
					$this->$key = $obj->default_value;
				}
			}
		}

		/* Other data */
		if ( $this->_new OR $database->_comment_bump & \IPS\dcudash\Databases::BUMP_ON_EDIT )
		{
			$this->record_saved   = time();
			$this->record_updated = time();
		}

		$this->record_allow_comments   = isset( $values['record_allow_comments'] ) ? $values['record_allow_comments'] : ( ! $this->record_locked );
		
		if ( isset( $values[ 'content_field_' . $database->field_title ] ) )
		{
			$this->record_dynamic_furl     = \IPS\Http\Url\Friendly::seoTitle( $values[ 'content_field_' . $database->field_title ] );
		}

		if ( isset( $values['record_static_furl_set'] ) and $values['record_static_furl_set'] and isset( $values['record_static_furl'] ) and $values['record_static_furl'] )
		{
			$newFurl = \IPS\Http\Url\Friendly::seoTitle( $values['record_static_furl'] );

			if ( $newFurl != $this->record_static_furl )
			{
				$this->storeUrl();
			}
			
			$this->record_static_furl = $newFurl;
		}
		else
		{
			if( $this->_new )
			{
				$this->record_static_furl = NULL;
			}
			/* Only remove the custom set furl if we are editing, we have the fields set, and they are empty. Otherwise an admin may have set the furl and then changed the author
				to a user who does not have permission to set the furl in which case we don't want it being reset */
			elseif ( isset( $values['record_static_furl_set'] ) and ( !$values['record_static_furl_set'] OR !isset( $values['record_static_furl'] ) OR !$values['record_static_furl'] ) )
			{
				$this->record_static_furl = NULL;
			}
		}
		
		if ( $this->_new )
		{
			/* Set the author ID on 'new' only */
			$this->member_id = (int) \IPS\Member::loggedIn()->member_id;
		}
		else
		{
			$this->sendQuoteAndMentionNotifications( array_unique( array_merge( $afterEditNotificationsExclude['quotes'], $afterEditNotificationsExclude['mentions'] ) ) );
		}
		
		if ( isset( $values['content_record_form_container'] ) )
		{
			$this->category_id = ( $values['content_record_form_container'] === 0 ) ? 0 : $values['content_record_form_container']->id;
		}

		$isNew    = $this->_new;
		$idColumn = static::$databaseColumnId;
		if ( ! $this->$idColumn )
		{
			$this->save();
		}

		/* Check for relational fields and claim attachments once we have an ID */
		foreach( $fieldObjects as $id => $row )
		{
			if ( $row->can( ( $isNew ? 'add' : 'edit' ) ) and $row->type == 'Editor' )
			{
				\IPS\File::claimAttachments( 'RecordField_' . ( $isNew ? 'new' : $this->_id ) . '_' . $row->id, $this->primary_id_field, $id, static::$customDatabaseId );
			}
			
			if ( $row->can( ( $isNew ? 'add' : 'edit' ) ) and $row->type == 'Upload' )
			{
				
				if ( $row->extra['type'] == 'image' and isset( $row->extra['thumbsize'] ) )
				{
					$dims = $row->extra['thumbsize'];
					$field = 'field_' . $row->id;
					$extra = $row->extra;
					$thumbs = iterator_to_array( \IPS\Db::i()->select( '*', 'dcudash_database_fields_thumbnails', array( array( 'thumb_field_id=?', $row->id ) ) )->setKeyField('thumb_original_location')->setValueField('thumb_location') );
					
					if ( $this->$field  )
					{
						foreach( explode( ',', $this->$field ) as $img )
						{
							try
							{
								$original = \IPS\File::get( 'dcudash_Records', $img );
								
								try
								{								
									$thumb = $original->thumbnail( 'dcudash_Records', $dims[0], $dims[1] );
									
									if ( isset( $thumbs[ (string) $original ] ) )
									{
										\IPS\Db::i()->delete( 'dcudash_database_fields_thumbnails', array( array( 'thumb_original_location=? and thumb_field_id=? and thumb_record_id=?', (string) $original, $row->id, $this->primary_id_field ) ) );
										
										try
										{
											\IPS\File::get( 'dcudash_Records', $thumbs[ (string) $original ] )->delete();
										}
										catch ( \Exception $e ) { }
									}
									
									\IPS\Db::i()->insert( 'dcudash_database_fields_thumbnails', array(
										'thumb_original_location' => (string) $original,
										'thumb_location'		  => (string) $thumb,
										'thumb_field_id'		  => $row->id,
										'thumb_database_id'		  => static::$customDatabaseId,
										'thumb_record_id'		  => $this->primary_id_field
									) );
								}
								catch ( \Exception $e ) { }
							}
							catch ( \Exception $e ) { }
						}
				
						/* Remove any thumbnails if the original has been removed */
						$orphans = iterator_to_array( \IPS\Db::i()->select( '*', 'dcudash_database_fields_thumbnails', array( array( 'thumb_record_id=?', $this->primary_id_field ), array( 'thumb_field_id=?', $row->id ), array( \IPS\Db::i()->in( 'thumb_original_location', explode( ',', $this->$field ), TRUE ) ) ) ) );
						
						if ( count( $orphans ) )
						{
							foreach( $orphans as $thumb )
							{
								try
								{
									\IPS\File::get( 'dcudash_Records', $thumb['thumb_location'] )->delete();
								}
								catch ( \Exception $e ) { }
							}
							
							\IPS\Db::i()->delete( 'dcudash_database_fields_thumbnails', array( array( 'thumb_record_id=?', $this->primary_id_field ), array( 'thumb_field_id=?', $row->id ), array( \IPS\Db::i()->in( 'thumb_original_location', explode( ',', $this->$field ), TRUE ) ) ) );
						}
					}
				}
			}
			
			if ( $row->can( ( $isNew ? 'add' : 'edit' ) ) and $row->type == 'Item' )
			{
				\IPS\Db::i()->delete( 'dcudash_database_fields_reciprocal_map', array( 'map_origin_database_id=? and map_field_id=? and map_origin_item_id=?', static::$customDatabaseId, $row->id, $this->_id ) );
				
				$field = 'field_' . $row->id;
				$extra = $row->extra;
				if ( $this->$field and ! empty( $extra['database'] ) )
				{
					foreach( explode( ',', $this->$field ) as $foreignId )
					{
						if ( $foreignId )
						{
							\IPS\Db::i()->insert( 'dcudash_database_fields_reciprocal_map', array(
								'map_origin_database_id'	=> static::$customDatabaseId,
								'map_foreign_database_id'   => $extra['database'],
								'map_origin_item_id'		=> $this->$idColumn,
								'map_foreign_item_id'		=> $foreignId,
								'map_field_id'				=> $row->id
							) );
						}
					}
				}
			}
		}

		parent::processForm( $values );
	}

	/**
	 * Stores the URL so when its changed, the old can 301 to the new location
	 *
	 * @return void
	 */
	public function storeUrl()
	{
		if ( $this->record_static_furl )
		{
			\IPS\Db::i()->insert( 'dcudash_url_store', array(
				'store_path'       => $this->record_static_furl,
			    'store_current_id' => $this->_id,
			    'store_type'       => 'record'
			) );
		}
	}

	/**
	 * Stats for table view
	 *
	 * @param	bool	$includeFirstCommentInCommentCount	Determines whether the first comment should be inlcluded in the comment count (e.g. For "posts", use TRUE. For "replies", use FALSE)
	 * @return	array
	 */
	public function stats( $includeFirstCommentInCommentCount=TRUE )
	{
		$return = array();

		if ( static::$commentClass and static::database()->options['comments'] )
		{
			$return['comments'] = (int) $this->mapped('num_comments');
		}

		if ( $this instanceof \IPS\Content\Views )
		{
			$return['num_views'] = (int) $this->mapped('views');
		}

		return $return;
	}

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		if ( ! $this->recordDash )
		{
			/* If we're coming through the database controller embedded in a dash, $currentDash will be set. If we're coming in via elsewhere, we need to fetch the dash */
			try
			{
				$this->recordDash = \IPS\dcudash\Dashes\Dash::loadByDatabaseId( static::$customDatabaseId );
			}
			catch( \OutOfRangeException $ex )
			{
				if ( \IPS\dcudash\Dashes\Dash::$currentDash )
				{
					$this->recordDash = \IPS\dcudash\Dashes\Dash::$currentDash;
				}
				else
				{
					throw new \LogicException;
				}
			}
		}

		if ( $this->recordDash )
		{
			$dashPath   = $this->recordDash->full_path;
			$class		= '\IPS\dcudash\Categories' . static::$customDatabaseId;
			$catPath    = $class::load( $this->category_id )->full_path;
			$recordSlug = ! $this->record_static_furl ? $this->record_dynamic_furl . '-r' . $this->primary_id_field : $this->record_static_furl;

			if ( static::database()->use_categories )
			{
				$url = \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dash&path=" . $dashPath . '/' . $catPath . '/' . $recordSlug, 'front', 'content_dash_path', $recordSlug );
			}
			else
			{
				$url = \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dash&path=" . $dashPath . '/' . $recordSlug, 'front', 'content_dash_path', $recordSlug );
			}
		}

		if ( $action )
		{
			$url = $url->setQueryString( 'do', $action );
			$url = $url->setQueryString( 'd' , static::database()->id );
			$url = $url->setQueryString( 'id', $this->primary_id_field );
		}

		return $url;
	}
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns()
	{
		$return = parent::basicDataColumns();
		$return[] = 'category_id';
		$return[] = 'record_static_furl';
		$return[] = 'record_dynamic_furl';
		return $return;
	}
	
	/**
	 * Query to get additional data for search result / stream view
	 *
	 * @param	array	$items	Item data (will be an array containing values from basicDataColumns())
	 * @return	array
	 */
	public static function searchResultExtraData( $items )
	{
		$categoryIds = array();
		
		foreach ( $items as $item )
		{
			if ( $item['category_id'] )
			{
				$categoryIds[ $item['category_id'] ] = $item['category_id'];
			}
		}
		
		if ( count( $categoryIds ) )
		{
			$categoryPaths = iterator_to_array( \IPS\Db::i()->select( array( 'category_id', 'category_full_path' ), 'dcudash_database_categories', \IPS\Db::i()->in( 'category_id', $categoryIds ) )->setKeyField('category_id')->setValueField('category_full_path') );
			
			$return = array();
			foreach ( $items as $item )
			{
				if ( $item['category_id'] )
				{
					$return[ $item['primary_id_field'] ] = $categoryPaths[ $item['category_id'] ];
				}
			}
			return $return;
		}
		
		return array();
	}
	
	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @return	\IPS\Http\Url
	 */
	public static function urlFromIndexData( $indexData, $itemData )
	{
		if ( static::$dashPath === NULL )
		{
			static::$dashPath = \IPS\Db::i()->select( array( 'dash_full_path' ), 'dcudash_dashes', array( 'dash_id=?', static::database()->dash_id ) )->first();
		}
		
		$recordSlug = !$itemData['record_static_furl'] ? $itemData['record_dynamic_furl']  . '-r' . $itemData['primary_id_field'] : $itemData['record_static_furl'];
		
		if ( static::database()->use_categories )
		{
			return \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dash&path=" . static::$dashPath . '/' . $itemData['extra'] . '/' . $recordSlug, 'front', 'content_dash_path', $recordSlug );
		}
		else
		{
			return \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=dash&path=" . static::$dashPath . '/' . $recordSlug, 'front', 'content_dash_path', $recordSlug );
		}
	}

	/**
	 * Template helper method to fetch custom fields to display
	 *
	 * @param   string  $type       Type of display
	 * @return  array
	 */
	public function customFieldsForDisplay( $type='display' )
	{
		if ( ! isset( $this->customDisplayFields['all'][ $type ] ) )
		{
			$fieldsClass = '\IPS\dcudash\Fields' . static::$customDatabaseId;
			$this->customDisplayFields['all'][ $type ] = $fieldsClass::display( $this->fieldValues(), $type, $this->container(), 'key', $this );
		}

		return $this->customDisplayFields['all'][ $type ];
	}

	/**
	 * @param mixed      $key       Key to fetch
	 * @param string     $type      Type of display to fetch
	 *
	 * @return mixed
	 */
	public function customFieldDisplayByKey( $key, $type='display' )
	{
		$fieldsClass = '\IPS\dcudash\Fields' . static::$customDatabaseId;

		if ( ! isset( $this->customDisplayFields[ $key ][ $type ] ) )
		{
			foreach ( $fieldsClass::roots( 'view' ) as $row )
			{
				if ( $row->key === $key )
				{
					$field = 'field_' . $row->id;
					$value = ( $this->$field !== '' AND $this->$field !== NULL ) ? $this->$field : $row->default_value;

					$this->customDisplayFields[ $key ][ $type ] = $row->formatForDisplay( $row->displayValue( $value ), $value, $type, $this );
				}
			}
		}

		/* Still nothing? */
		if ( ! isset( $this->customDisplayFields[ $key ][ $type ] ) )
		{
			$this->customDisplayFields[ $key ][ $type ] = NULL;
		}

		return $this->customDisplayFields[ $key ][ $type ];
	}

	/**
	 * Get custom field_x keys and values
	 *
	 * @param	boolean	$allData	All data (true) or just custom field data (false)
	 * @return	array
	 */
	public function fieldValues( $allData=FALSE )
	{
		$fields = array();
		
		foreach( $this->_data as $k => $v )
		{
			if ( $allData === TRUE OR mb_substr( $k, 0, 6 ) === 'field_')
			{
				$fields[ $k ] = $v;
			}
		}

		return $fields;
	}
	
	/**
	 * Returns the content images
	 *
	 * @param	int|null	$limit	Number of attachments to fetch, or NULL for all
	 *
	 * @return	array|NULL	If array, then array( 'core_Attachment' => 'month_x/foo.gif', ... );
	 * @throws	\BadMethodCallException
	 */
	public function contentImages( $limit = NULL )
	{
		$idColumn = static::$databaseColumnId;
		$attachments = array();
		
		$internal = \IPS\Db::i()->select( 'attachment_id', 'core_attachments_map', array( 'location_key=? and id1=? and id3=?', 'dcudash_Records', $this->$idColumn, static::$customDatabaseId ) );
		
		/* Attachments */
		foreach( \IPS\Db::i()->select( '*', 'core_attachments', array( array( 'attach_id IN(?)', $internal ), array( 'attach_is_image=1' ) ), 'attach_id ASC', $limit ) as $row )
		{
			$attachments[] = array( 'core_Attachment' => $row['attach_location'] );
		}
		
		/* Record image */
		if ( $this->record_image )
		{
			$attachments[] = array( 'dcudash_Records' => $this->record_image );
		}
		
		/* Any upload fields */
		$categoryClass = 'IPS\dcudash\Categories' . static::$customDatabaseId;
		$container = $categoryClass::load( $this->category_id );
		$fieldsClass  = 'IPS\dcudash\Fields' . static::$customDatabaseId;
		$fieldValues = $this->fieldValues();
		$customFields = $fieldsClass::fields( $fieldValues, 'edit', $container, 0, $this );

		foreach( $customFields as $key => $field )
		{
			$fieldName = mb_substr( $field->name, 8 );
			if ( get_class( $field ) == 'IPS\Helpers\Form\Upload' )
			{
				if ( is_array( $fieldValues[ $fieldName ] ) )
				{
					foreach( $fieldValues[ $fieldName ] as $fileName )
					{
						$obj = \IPS\File::get( 'dcudash_Records', $fileName );
						if ( $obj->isImage() )
						{
							$attachments[] = array( 'dcudash_Records' => $fileName );
						}
					}
				}
				else
				{
					$obj = \IPS\File::get( 'dcudash_Records', $fieldValues[ $fieldName ] );
					if ( $obj->isImage() )
					{
						$attachments[] = array( 'dcudash_Records' => $fieldValues[ $fieldName ] );
					}
				}
			}
		}
		
		return count( $attachments ) ? $attachments : NULL;
	}

	/**
	 * Get the post key or create one if one doesn't exist
	 *
	 * @return  string
	 */
	public function get__post_key()
	{
		return ! empty( $this->post_key ) ? $this->post_key : md5( mt_rand() );
	}

	/**
	 * Get the publish date
	 *
	 * @return	string
	 */
	public function get__publishDate()
	{
		return $this->record_publish_date ? $this->record_publish_date : $this->record_saved;
	}

	/**
	 * Get the record id
	 *
	 * @return	int
	 */
	public function get__id()
	{
		return $this->primary_id_field;
	}
	
	/**
	 * Get value from data store
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( $key )
	{
		$val = parent::__get( $key );
		
		if ( $val === NULL )
		{
			if ( mb_substr( $key, 0, 6 ) === 'field_' and ! preg_match( '/^[0-9]+?/', mb_substr( $key, 6 ) ) )
			{
				$realKey = mb_substr( $key, 6 );
				if ( $this->customValueFields === NULL )
				{
					$fieldsClass = '\IPS\dcudash\Fields' . static::$customDatabaseId;
					
					foreach ( $fieldsClass::roots( 'view' ) as $row )
					{
						$field = 'field_' . $row->id; 
						$this->customValueFields[ $row->key ] = array( 'id' => $row->id, 'content' => $this->$field );
					}
				}
				
				if ( isset( $this->customValueFields[ $realKey ] ) )
				{
					$val = $this->customValueFields[ $realKey ]['content'];
				} 
			}
		}
		
		return $val;
	}
	
	/**
	 * Set value in data store
	 *
	 * @see		\IPS\Patterns\ActiveRecord::save
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( $key, $value )
	{
		if ( $key == 'field_' . static::database()->field_title )
		{
			$this->displayTitle = NULL;
		}
		if ( $key == 'field_' . static::database()->field_content )
		{
			$this->displayContent = NULL;
		}
		
		if ( mb_substr( $key, 0, 6 ) === 'field_' )
		{
			$realKey = mb_substr( $key, 6 );
			
			if ( preg_match( '/^[0-9]+?/', $realKey ) )
			{
				/* Wipe any stored values */
				$this->customValueFields = NULL;
			}
			else
			{
				/* This is setting by key */
				if ( $this->customValueFields === NULL )
				{
					$fieldsClass = '\IPS\dcudash\Fields' . static::$customDatabaseId;
					
					foreach ( $fieldsClass::roots( 'view' ) as $row )
					{
						$field = 'field_' . $row->id; 
						$this->customValueFields[ $row->key ] = array( 'id' => $row->id, 'content' => $this->$field );
					}
				}
			
				$field = 'field_' . $this->customValueFields[ $realKey ]['id'];
				$this->$field = $value;
				
				$this->customValueFields[ $realKey ]['content'] = $value;
				
				/* Rest key for the parent::__set() */
				$key = $field;
			}
		}
		
		parent::__set( $key, $value );
	}

	/**
	 * Get the record title for display
	 *
	 * @return	string
	 */
	public function get__title()
	{
		$field = 'field_' . static::database()->field_title;

		try
		{
			if ( ! $this->displayTitle )
			{
				$class = '\IPS\dcudash\Fields' .  static::database()->id;
				$this->displayTitle = $class::load( static::database()->field_title )->displayValue( $this->$field );
			}

			return $this->displayTitle;
		}
		catch( \Exception $e )
		{
			return $this->$field;
		}
	}
	
	/**
	 * Get the record content for display
	 *
	 * @return	string
	 */
	public function get__content()
	{
		$field = 'field_' . static::database()->field_content;

		try
		{
			if ( ! $this->displayContent )
			{
				$class = '\IPS\dcudash\Fields' .  static::database()->id;

				$this->displayContent = $class::load( static::database()->field_content )->displayValue( $this->$field );
			}

			return $this->displayContent;
		}
		catch( \Exception $e )
		{
			return $this->$field;
		}
	}
	
	/**
	 * Return forum sync on or off
	 *
	 * @return	int
	 */
	public function get__forum_record()
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_record;
		}
		
		return static::database()->forum_record;
	}
	
	/**
	 * Return forum post on or off
	 *
	 * @return	int
	 */
	public function get__forum_comments()
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_comments;
		}
		
		return static::database()->forum_comments;
	}
	
	/**
	 * Return forum sync delete
	 *
	 * @return	int
	 */
	public function get__forum_delete()
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_delete;
		}
		
		return static::database()->forum_delete;
	}
	
	/**
	 * Return forum sync forum
	 *
	 * @return	int
	 */
	public function get__forum_forum()
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_forum;
		}
		
		return static::database()->forum_forum;
	}
	
	/**
	 * Return forum sync prefix
	 *
	 * @return	int
	 */
	public function get__forum_prefix()
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_prefix;
		}
	
		return static::database()->forum_prefix;
	}
	
	/**
	 * Return forum sync suffix
	 *
	 * @return	int
	 */
	public function get__forum_suffix()
	{
		if ( $this->container()->forum_override and static::database()->use_categories )
		{
			return $this->container()->forum_suffix;
		}
	
		return static::database()->forum_suffix;
	}

	/**
	 * Return record image thumb
	 *
	 * @return	int
	 */
	public function get__record_image_thumb()
	{
		return $this->record_image_thumb ? $this->record_image_thumb : $this->record_image;
	}

	/**
	 * Get edit line
	 *
	 * @return	string|NULL
	 */
	public function editLine()
	{
		if ( $this->record_edit_time and ( $this->record_edit_show or \IPS\Member::loggedIn()->modPermission('can_view_editlog') ) and \IPS\Settings::i()->edit_log )
		{
			return \IPS\dcudash\Theme::i()->getTemplate( static::database()->template_display, 'dcudash', 'database' )->recordEditLine( $this );
		}
		return NULL;
	}

	/**
	 * Get mapped value
	 *
	 * @param	string	$key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( $key )
	{
		if ( $key === 'title' )
		{
			return $this->_title;
		}
		else if ( $key === 'content' )
		{
			return $this->_content;
		}
		
		if ( isset( static::$databaseColumnMap[ $key ] ) )
		{
			$field = static::$databaseColumnMap[ $key ];
				
			if ( is_array( $field ) )
			{
				$field = array_pop( $field );
			}
				
			return $this->$field;
		}
		return NULL;
	}
	
	/**
	 * Save
	 *
	 * @return void
	 */
	public function save()
	{
		$new = $this->_new;
			
		if ( $new OR static::database()->_comment_bump & \IPS\dcudash\Databases::BUMP_ON_EDIT )
		{
			$member = \IPS\Member::load( $this->member_id );
	
			/* Set last comment as record so that category listing is correct */
			if ( $this->record_saved > $this->record_last_comment )
			{
				$this->record_last_comment = $this->record_saved;
			}

			if ( $new )
			{
				$this->record_last_comment_by   = $this->member_id;
				$this->record_last_comment_name = $member->name;
			}
		}
	
		parent::save();

		if ( $this->category_id )
		{
			unset( static::$multitons[ $this->primary_id_field ] );
			
			foreach( static::$multitonMap as $fieldKey => $data )
			{
				foreach( $data as $fieldValue => $primaryId )
				{
					if( $primaryId == $this->primary_id_field )
					{
						unset( static::$multitonMap[ $fieldKey ][ $fieldValue ] );
					}
				}
			}
			
            $class = '\IPS\dcudash\Categories' . static::$customDatabaseId;
            $category = $class::load( $this->category_id );
            $category->setLastComment();
			$category->setLastReview();
            $category->save();
        }
	}
	
	/**
	 * Resync last comment
	 *
	 * @return	void
	 */
	public function resyncLastComment()
	{
		if ( $this->useForumComments() )
		{
			if ( $topic = $this->topic( FALSE ) )
			{
				$topic->resyncLastComment();
			}
		}
		
		parent::resyncLastComment();
	}
	
	/**
	 * Utility method to reset the last commenter of a record
	 *
	 * @param   boolean     $setCategory    Check and set the last commenter for a category
	 * @return void
	 */
	public function resetLastComment( $setCategory=false )
	{
		$comment = $this->comments( 1, 0, 'date', 'desc', NULL, FALSE );

		if ( $comment )
		{
			$this->record_last_comment      = $comment->mapped('date');
			$this->record_last_comment_by   = $comment->author()->member_id;
			$this->record_last_comment_name = $comment->author()->name;
			$this->save();

			if ( $setCategory and $this->category_id )
			{
				$class = '\IPS\dcudash\Categories' . static::$customDatabaseId;
				$class::load( $this->category_id )->setLastComment( NULL );
				$class::load( $this->category_id )->save();
			}
		}
	}

	/**
	 * Resync the comments/unapproved comment counts
	 *
	 * @param	string	$commentClass	Override comment class to use
	 * @return void
	 */
	public function resyncCommentCounts( $commentClass=NULL )
	{
		if ( $this->useForumComments() )
		{
			$topic = $this->topic( FALSE );

			if ( $topic )
			{
				$this->record_comments = $topic->posts - 1;
				$this->record_comments_queued = $topic->topic_queuedposts;
				$this->record_comments_hidden = $topic->topic_hiddenposts;
				$this->save();
			}
		}
		else
		{
			parent::resyncCommentCounts( $commentClass );
		}
	}
	
	/**
	 * Are comments supported by this class?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	\IPS\Node\Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsComments( \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		return parent::supportsComments() and static::database()->options['comments'];
	}
	
	/**
	 * Are reviews supported by this class?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for or NULL to not check permission
	 * @param	\IPS\Node\Model|NULL	$container	The container to check in, or NULL for any container
	 * @return	bool
	 */
	public static function supportsReviews( \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		return parent::supportsReviews() and static::database()->options['reviews'];
	}
	
	/* !Relational Fields */
	/**
	 * Returns an array of Content items that have been linked to from another database.
	 * I think at least. The concept makes perfect sense until I think about it too hard.
	 *
	 * @note The returned array is in the format of {field_id} => array( object, object... )
	 *
	 * @return FALSE|array
	 */
	public function getReciprocalItems()
	{
		/* Check to see if any fields are linking to this database in this easy to use method wot I writted myself */
		if ( \IPS\dcudash\Databases::hasReciprocalLinking( static::database()->_id ) )
		{
			$return = array();
			/* Oh that's just lovely then. Lets be a good fellow and fetch the items then! */
			foreach( \IPS\Db::i()->select( '*', 'dcudash_database_fields_reciprocal_map', array( 'map_foreign_database_id=? and map_foreign_item_id=?', static::database()->_id, $this->primary_id_field ) ) as $record )
			{
				try
				{
					$recordClass = 'IPS\dcudash\Records' . $record['map_origin_database_id'];
					$return[ $record['map_field_id'] ][] = $recordClass::load( $record['map_origin_item_id'] );
				}
				catch ( \Exception $ex ) { }
			}
			
			/* Has something gone all kinds of wonky? */
			if ( ! count( $return ) )
			{
				return FALSE;
			}

			return $return;
		}

		return FALSE;
	}
	
	/* !IP.Board Integration */
	
	/**
	 * Use forum for comments
	 *
	 * @return boolean
	 */
	public function useForumComments()
	{
		return $this->_forum_record and $this->_forum_comments and $this->record_topicid and \IPS\Application::appIsEnabled('forums');
	}
	
	/**
	 * Do Moderator Action
	 *
	 * @param	string				$action	The action
	 * @param	\IPS\Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param	string|NULL			$reason	Reason (for hides)
	 * @param	bool				$immediately	Delete Immediately
	 * @return	void
	 * @throws	\OutOfRangeException|\InvalidArgumentException|\RuntimeException
	 */
	public function modAction( $action, \IPS\Member $member = NULL, $reason = NULL, $immediately = FALSE )
	{
		parent::modAction( $action, $member, $reason, $immediately );
		
		if ( $this->useForumComments() and ( $action === 'lock' or $action === 'unlock' ) )
		{
			if ( $topic = $this->topic() )
			{
				$topic->state = ( $action === 'lock' ? 'closed' : 'open' );
				$topic->save();	
			}
		}
	}

	/**
	 * Get comments
	 *
	 * @param	int|NULL			$limit					The number to get (NULL to use static::getCommentsPerDash())
	 * @param	int|NULL			$offset					The number to start at (NULL to examine \IPS\Request::i()->dash)
	 * @param	string				$order					The column to order by
	 * @param	string				$orderDirection			"asc" or "desc"
	 * @param	\IPS\Member|NULL	$member					If specified, will only get comments by that member
	 * @param	bool|NULL			$includeHiddenComments	Include hidden comments or not? NULL to base of currently logged in member's permissions
	 * @param	\IPS\DateTime|NULL	$cutoff					If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param	mixed				$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @param	bool|NULL			$bypassCache			Used in cases where comments may have already been loaded i.e. splitting comments on an item.
	 * @param	bool				$includeDeleted			Include deleted content.
	 * @return	array|NULL|\IPS\Content\Comment	If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public function comments( $limit=NULL, $offset=NULL, $order='date', $orderDirection='asc', $member=NULL, $includeHiddenComments=NULL, $cutoff=NULL, $extraWhereClause=NULL, $bypassCache=FALSE, $includeDeleted=FALSE, $canViewWarn=NULL )
	{
		if ( $this->useForumComments() )
		{
			$recordClass = 'IPS\dcudash\Records\RecordsTopicSync' . static::$customDatabaseId;

			/* If we are pulling in ASC order we want to jump up by 1 to account for the first post, which is not a comment */
			if( mb_strtolower( $orderDirection ) == 'asc' )
			{
				$_dashValue = ( \IPS\Request::i()->dash ? intval( \IPS\Request::i()->dash ) : 1 );

				if( $_dashValue < 1 )
				{
					$_dashValue = 1;
				}
				
				$offset = ( ( $_dashValue - 1 ) * static::getCommentsPerDash() ) + 1;
			}
			
			return $recordClass::load( $this->record_topicid )->comments( $limit, $offset, $order, $orderDirection, $member, $includeHiddenComments, $cutoff, $extraWhereClause, $bypassCache, $includeDeleted );
		}
		else
		{
			/* Because this is a static property, it may have been overridden by a block on the same dash. */
			if ( get_called_class() != 'IPS\dcudash\Records\RecordsTopicSync' . static::$customDatabaseId )
			{
				static::$commentClass = 'IPS\dcudash\Records\Comment' . static::$customDatabaseId;
			}
		}

		$where = NULL;
		if( static::$commentClass != 'IPS\dcudash\Records\CommentTopicSync' . static::$customDatabaseId )
		{
			$where = array( array( 'comment_database_id=?', static::$customDatabaseId ) );
		}
		
		return parent::comments( $limit, $offset, $order, $orderDirection, $member, $includeHiddenComments, $cutoff, $where, $bypassCache, $includeDeleted );
	}

	/**
	 * Get review dash count
	 *
	 * @return	int
	 */
	public function reviewDashCount()
	{
		if ( $this->reviewDashCount === NULL )
		{
			$reviewClass = static::$reviewClass;
			$idColumn = static::$databaseColumnId;
			$where = array( array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $this->$idColumn ) );
			$where[] = array( 'review_database_id=?', static::$customDatabaseId );
			$count = $reviewClass::getItemsWithPermission( $where, NULL, NULL, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );
			$this->reviewDashCount = ceil( $count / static::$reviewsPerDash );

			if( $this->reviewDashCount < 1 )
			{
				$this->reviewDashCount	= 1;
			}
		}
		return $this->reviewDashCount;
	}

	/**
	 * Get reviews
	 *
	 * @param	int|NULL			$limit					The number to get (NULL to use static::getCommentsPerDash())
	 * @param	int|NULL			$offset					The number to start at (NULL to examine \IPS\Request::i()->dash)
	 * @param	string				$order					The column to order by (NULL to examine \IPS\Request::i()->sort)
	 * @param	string				$orderDirection			"asc" or "desc" (NULL to examine \IPS\Request::i()->sort)
	 * @param	\IPS\Member|NULL	$member					If specified, will only get comments by that member
	 * @param	bool|NULL			$includeHiddenReviews	Include hidden comments or not? NULL to base of currently logged in member's permissions
	 * @param	\IPS\DateTime|NULL	$cutoff					If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param	mixed				$extraWhereClause		Additional where clause(s) (see \IPS\Db::build for details)
	 * @param	bool				$includeDeleted			Include deleted content
	 * @return	array|NULL|\IPS\Content\Comment	If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public function reviews( $limit=NULL, $offset=NULL, $order=NULL, $orderDirection='desc', $member=NULL, $includeHiddenReviews=NULL, $cutoff=NULL, $extraWhereClause=NULL, $includeDeleted=FALSE, $canViewWarn=NULL )
	{
		$where = array( array( 'review_database_id=?', static::$customDatabaseId ) );

		return parent::reviews( $limit, $offset, $order, $orderDirection, $member, $includeHiddenReviews, $cutoff, $where, $includeDeleted );
	}

	/**
	 * Get available comment/review tabs
	 *
	 * @return	array
	 */
	public function commentReviewTabs()
	{
		$tabs = array();
		if ( static::database()->options['reviews'] )
		{
			$tabs['reviews'] = \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_review_count', TRUE, array( 'pluralize' => array( $this->mapped('num_reviews') ) ) );
		}
		if ( static::database()->options['comments'] )
		{
			$count = $this->mapped('num_comments');
			if ( \IPS\Application::appIsEnabled('forums') and $this->_forum_comments and $topic = $this->topic() )
			{
				if ( $count != ( $topic->posts - 1 ) )
				{
					$this->record_comments = $topic->posts - 1;
					$this->save();
				}
				
				$count = ( $topic->posts - 1 ) > 0 ? $topic->posts - 1 : 0;
			}
			
			$tabs['comments'] = \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_comment_count', TRUE, array( 'pluralize' => array( $count ) ) );
		}

		return $tabs;
	}

	/**
	 * Get comment/review output
	 *
	 * @param	string	$tab	Active tab
	 * @return	string
	 */
	public function commentReviews( $tab )
	{
		if ( $tab === 'reviews' )
		{
			return \IPS\dcudash\Theme::i()->getTemplate( static::database()->template_display, 'dcudash', 'database' )->reviews( $this );
		}
		elseif( $tab === 'comments' )
		{
			return \IPS\dcudash\Theme::i()->getTemplate( static::database()->template_display, 'dcudash', 'database' )->comments( $this );
		}

		return '';
	}

	/**
	 * Should new items be moderated?
	 *
	 * @param	\IPS\Member		$member		The member posting
	 * @param	\IPS\Node\Model	$container	The container
	 * @return	bool
	 */
	public static function moderateNewItems( \IPS\Member $member, \IPS\Node\Model $container = NULL )
	{
		if ( static::database()->record_approve and !$member->group['g_avoid_q'] )
		{
			return !static::modPermission( 'approve', $member, $container );
		}

		return parent::moderateNewItems( $member, $container );
	}

	/**
	 * Should new comments be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewComments( \IPS\Member $member )
	{
		return ( static::database()->options['comments_mod'] and !$member->group['g_avoid_q'] ) or parent::moderateNewComments( $member );
	}

	/**
	 * Should new reviews be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewReviews( \IPS\Member $member )
	{
		return ( static::database()->options['reviews_mod'] and !$member->group['g_avoid_q'] ) or parent::moderateNewReviews( $member );
	}

	/**
	 * @brief Skip topic creation, useful if the topic may already exist
	 */
	public static $skipTopicCreation = FALSE;

	/**
	 * Create from form
	 *
	 * @param	array					$values				Values from form
	 * @param	\IPS\Node\Model|NULL	$container			Container (e.g. forum), if appropriate
	 * @param	bool					$sendNotification	Send Notification
	 * @return	\IPS\dcudash\Records
	 */
	public static function createFromForm( $values, \IPS\Node\Model $container = NULL, $sendNotification = TRUE )
	{
		$record = parent::createFromForm( $values, $container, $sendNotification );

		if ( !static::$skipTopicCreation and \IPS\Application::appIsEnabled('forums') and $record->_forum_record and $record->_forum_forum and ! $record->hidden() and ! $record->record_future_date )
		{
			try
			{
				$record->syncTopic();
			}
			catch( \Exception $ex ) { }
		}

		return $record;
	}

	/**
	 * Process after the object has been edited on the front-end
	 *
	 * @param	array	$values		Values from form
	 * @return	void
	 */
	public function processAfterEdit( $values )
	{
		if ( \IPS\Application::appIsEnabled('forums') and $this->_forum_record and $this->_forum_forum and ! $this->hidden() and ! $this->record_future_date )
		{
			try
			{
				$this->syncTopic();
			}
			catch( \Exception $ex ) { }
		}

		parent::processAfterEdit( $values );
	}

	/**
	 * Callback to execute when tags are edited
	 *
	 * @return	void
	 */
	protected function processAfterTagUpdate()
	{
		parent::processAfterTagUpdate();

		if ( \IPS\Application::appIsEnabled('forums') and $this->_forum_record and $this->_forum_forum and ! $this->hidden() and ! $this->record_future_date )
		{
			try
			{
				$this->syncTopic();
			}
			catch( \Exception $ex ) { }
		}
	}
	
	/**
	 * Process the comment form
	 *
	 * @param	array	$values		Array of $form values
	 * @return  \IPS\Content\Comment
	 */
	public function processCommentForm( $values )
	{
		if ( $this->useForumComments() )
		{
			$topic = $this->topic( FALSE );
		
			if ( $topic === NULL )
			{
				try
				{
					$this->syncTopic();
				}
				catch( \Exception $ex ) { }
				
				/* Try again */
				$topic = $this->topic( FALSE );
				if ( ! $topic )
				{
					return parent::processCommentForm( $values );
				}
			}
			
			$comment = $values[ static::$formLangPrefix . 'comment' . '_' . $this->_id ];
			$post    = \IPS\forums\Topic\Post::create( $topic, $comment, FALSE, ( isset( $values['guest_name'] ) ? $values['guest_name'] : NULL ) );
			
			$commentClass = 'IPS\dcudash\Records\CommentTopicSync' . static::$customDatabaseId;
			
			$topic->markRead();
			
			return $commentClass::load( $post->pid );
			
		}
		else
		{
			return parent::processCommentForm( $values );
		}
	}
	
	/**
	 * Syncing to run when hiding
	 *
	 * @param	\IPS\Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onHide( $member )
	{
		parent::onHide( $member );
		if ( \IPS\Application::appIsEnabled('forums') and $topic = $this->topic() )
		{
			$topic->hide( $member );
		}
	}
	
	/**
	 * Syncing to run when publishing something previously pending publishing
	 *
	 * @param	\IPS\Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onPublish( $member )
	{
		parent::onPublish( $member );

		/* If last topic/review columns are in the future, reset them or the content will indefinitely show as unread */
		$this->record_last_review = ( $this->record_last_review > $this->record_publish_date ) ? $this->record_publish_date : $this->record_last_review;
		$this->record_last_comment = ( $this->record_last_comment > $this->record_publish_date ) ? $this->record_publish_date : $this->record_last_comment;
		$this->save();

		if ( \IPS\Application::appIsEnabled('forums') )
		{
			if ( $topic = $this->topic() )
			{
				if ( $topic->hidden() )
				{
					$topic->unhide( $member );
				}
			}
			else if ( $this->_forum_forum )
			{
				try
				{
					$this->syncTopic();
				}
				catch( \Exception $ex ) { }
			}
		}
	}
	
	/**
	 * Syncing to run when unpublishing an item (making it a future dated entry when it was already published)
	 *
	 * @param	\IPS\Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnpublish( $member )
	{
		parent::onUnpublish( $member );
		if ( \IPS\Application::appIsEnabled('forums') AND $topic = $this->topic() )
		{
			$topic->hide( $member );
		}
	}
	
	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool					$approving	If true, is being approved for the first time
	 * @param	\IPS\Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnhide( $approving, $member )
	{
		parent::onUnhide( $approving, $member );
		
		if ( $this->record_expiry_date )
		{
			$this->record_expiry_date = 0;
			$this->save();
		}
		
		if ( \IPS\Application::appIsEnabled('forums') )
		{
			if ( $topic = $this->topic() )
			{ 
				$topic->unhide( $member );
			}
			elseif ( $this->_forum_forum and ! $this->isFutureDate() )
			{
				try
				{
					$this->syncTopic();
				}
				catch( \Exception $ex ) { };
			}
		}
	}

	/**
	 * Change Author
	 *
	 * @param	\IPS\Member	$newAuthor	The new author
	 * @return	void
	 */
	public function changeAuthor( \IPS\Member $newAuthor )
	{
		parent::changeAuthor( $newAuthor );

		$topic = $this->topic();

		if ( $topic )
		{
			$topic->changeAuthor( $newAuthor );
		}
	}
	
	/**
	 * Get last comment author
	 * Overloaded for the bump on edit shenanigans 
	 *
	 * @return	\IPS\Member
	 * @throws	\BadMethodCallException
	 */
	public function lastCommenter()
	{
		if ( ( static::database()->_comment_bump & ( \IPS\dcudash\Databases::BUMP_ON_EDIT + \IPS\dcudash\Databases::BUMP_ON_COMMENT ) and $this->record_edit_time > 0 and $this->record_edit_time > $this->record_last_comment ) OR
			 ( ( static::database()->_comment_bump & \IPS\dcudash\Databases::BUMP_ON_EDIT ) and !( static::database()->_comment_bump & ( \IPS\dcudash\Databases::BUMP_ON_EDIT + \IPS\dcudash\Databases::BUMP_ON_COMMENT ) ) and $this->record_edit_time > 0 ) )
		{
			try
			{
				$this->_lastCommenter = \IPS\Member::load( $this->record_edit_member_id );
				return $this->_lastCommenter;
			}
			catch( \Exception $e ) { }
		}
		
		return parent::lastCommenter();
	}
	
	/**
	 * Is this topic linked to a record?
     *
     * @param   \IPS\forums\Topic   $topic  Forums topic
	 * @return boolean
	 */
	public static function topicIsLinked( $topic )
	{
		foreach( \IPS\dcudash\Databases::databases() as $database )
		{
			try
			{
				if ( $database->forum_record and $database->forum_forum == $topic->container()->_id )
				{
					$class = '\IPS\dcudash\Records' . $database->id;
					$record = $class::load( $topic->tid, 'record_topicid' );
				
					if ( $record->_forum_record )
					{
						return TRUE;
					}
				}
			}
			catch( \Exception $e ) { }
		}
		
		return FALSE;
	}
	
	/**
	 * Is this topic linked to a record?
     *
     * @param   \IPS\forums\Topic   $topic  Forums topic
	 * @return  \IPS\dcudash\Records|NULL
	 */
	public static function getLinkedRecord( $topic )
	{
		foreach( \IPS\dcudash\Databases::databases() as $database )
		{
			try
			{
				if ( $database->forum_record and $database->forum_forum == $topic->container()->_id )
				{
					$class = '\IPS\dcudash\Records' . $database->id;
					$record = $class::load( $topic->tid, 'record_topicid' );
				
					if ( $record->_forum_record )
					{
						return $record;
					}
				}
			}
			catch( \Exception $e ) { }
		}
		
		return NULL;
	}
	
	/**
	 * Get Topic (checks member's permissions)
	 *
	 * @param	bool	$checkPerms		Should check if the member can read the topic?
	 * @return	\IPS\forums\Topic|NULL
	 */
	public function topic( $checkPerms=TRUE )
	{
		if ( \IPS\Application::appIsEnabled('forums') and $this->_forum_record and $this->record_topicid )
		{
			try
			{
				return $checkPerms ? \IPS\forums\Topic::loadAndCheckPerms( $this->record_topicid ) : \IPS\forums\Topic::load( $this->record_topicid );
			}
			catch ( \OutOfRangeException $e )
			{
				return NULL;
			}
		}
	
		return NULL;
	}

	/**
	 * Post this record as a forum topic
	 *
	 * @return void
	 */
	public function syncTopic()
	{
		if ( ! \IPS\Application::appIsEnabled( 'forums' ) )
		{
			throw new \UnexpectedValueException('content_record_no_forum_app_for_topic');
		}

		/* Fetch the forum */
		try
		{
			$forum = \IPS\forums\Forum::load( $this->_forum_forum );
		}
		catch( \OutOfRangeException $ex )
		{
			throw new \UnexpectedValueException('content_record_bad_forum_for_topic');
		}

		/* Run a test for the record url, this call will throw an LogicException if the database isn't associated to a dash */
		try
		{
			$this->url();
		}
		catch ( \LogicException $e )
		{
			$idColumn = static::$databaseColumnId;

			\IPS\Log::log( sprintf( "Record %s in database %s tried to sync the topic, but failed because it has no valid url", $this->$idColumn , static::$customDatabaseId), 'dcudash_topicsync' );
			return;
		}

		/* Existing topic */
		if ( $this->record_topicid )
		{
			/* Get */
			try
			{
				$topic = \IPS\forums\Topic::load( $this->record_topicid );
				if ( !$topic )
				{
					return;
				}
				/* Reset cache */
				$this->displayTitle = NULL;
				$topic->title = $this->_forum_prefix . $this->_title . $this->_forum_suffix;
				if ( \IPS\Settings::i()->tags_enabled )
				{
					$topic->setTags( $this->prefix() ? array_merge( $this->tags(), array( 'prefix' => $this->prefix() ) ) : $this->tags() );
				}
				
				if ( $this->hidden() )
				{
					$topic->hide( FALSE );
				}
				else if ( $topic->hidden() )
				{
					$topic->unhide( FALSE );
				}

				$topic->save();
				$firstPost = $topic->comments( 1 );

				$content = \IPS\Theme::i()->getTemplate( 'submit', 'dcudash', 'front' )->topic( $this );
				\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $content );

				$firstPost->post = $content;
				$firstPost->save();
				
				/* Reindex to update search index */
				\IPS\Content\Search\Index::i()->index( $firstPost );
			}
			catch ( \OutOfRangeException $e )
			{
				return;
			}
		}
		/* New topic */
		else
		{
			/* Create topic */
			$topic = \IPS\forums\Topic::createItem( $this->author(), \IPS\Request::i()->ipAddress(), \IPS\DateTime::ts( $this->record_publish_date ? $this->record_publish_date : $this->record_saved ), \IPS\forums\Forum::load( $this->_forum_forum ), $this->hidden() );
			$topic->title = $this->_forum_prefix . $this->_title . $this->_forum_suffix;
			$topic->topic_archive_status = \IPS\forums\Topic::ARCHIVE_EXCLUDE;
			$topic->save();

			if ( \IPS\Settings::i()->tags_enabled )
			{
				$topic->setTags( $this->prefix() ? array_merge( $this->tags(), array( 'prefix' => $this->prefix() ) ) : $this->tags() );
			}

			/* Create post */
			$content = \IPS\Theme::i()->getTemplate( 'submit', 'dcudash', 'front' )->topic( $this );
			\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $content );

			$post = \IPS\forums\Topic\Post::create( $topic, $content, TRUE, NULL, NULL, $this->author(), \IPS\DateTime::ts( $this->record_publish_date ? $this->record_publish_date : $this->record_saved ) );
			$post->save();

			$topic->topic_firstpost = $post->pid;
			$topic->save();

			$topic->markRead();
			
			/* Update file */
			$this->record_topicid = $topic->tid;
			$this->save();
			
			/* Reindex to update search index */
			\IPS\Content\Search\Index::i()->index( $post );
		}
	}

	/**
	 * Sync topic details to the record
	 *
	 * @param   \IPS\forums\Topic   $topic  Forums topic
	 * @return  void
	 */
	public function syncRecordFromTopic( $topic )
	{
		if ( $this->_forum_record and $this->_forum_forum and $this->_forum_comments )
		{
			$this->record_last_comment_by   = $topic->last_poster_id;
			$this->record_last_comment_name = $topic->last_poster_name;
			$this->record_last_comment      = $topic->last_post;
			$this->record_comments_queued   = $topic->topic_queuedposts;
			$this->record_comments_hidden 	= $topic->topic_hiddenposts;
			$this->record_comments          = $topic->posts - 1;
			$this->save();
		}
	}

	/**
	 * Get fields for the topic
	 * 
	 * @return array
	 */
	public function topicFields()
	{
		$fieldsClass = 'IPS\dcudash\Fields' . static::$customDatabaseId;
		$fieldData   = $fieldsClass::data( 'view', $this->container() );
		$fieldValues = $fieldsClass::display( $this->fieldValues(), 'record', $this->container(), 'id' );

		$fields = array();
		foreach( $fieldData as $id => $data )
		{
			if ( $data->topic_format )
			{
				if ( isset( $fieldValues[ $data->id ] ) )
				{
					$html = str_replace( '{title}'  , $data->_title, $data->topic_format );
					$html = str_replace( '{content}', $fieldValues[ $data->id ], $html );
					$html = str_replace( '{value}'  , $fieldValues[ $data->id ], $html );
				
					$fields[ $data->id ] = $html;
				}
			}
		}

		if ( ! count( $fields ) )
		{
			$fields[ static::database()->field_content ] = $fieldValues['content'];
		}

		return $fields;
	}
	
	/**
	 * @brief	Store the comment dash count otherwise $topic->posts is reduced by 1 each time it is called
	 */
	protected $recordCommentDashCount = NULL;
	
	/**
	 * Get comment dash count
	 *
	 * @param	bool		$recache		TRUE to recache the value
	 * @return	int
	 */
	public function commentDashCount( $recache=FALSE )
	{
		if ( $this->recordCommentDashCount === NULL or $recache === TRUE )
		{
			if ( $this->useForumComments() )
			{
				try
				{
					$topic = $this->topic();
	
					if( $topic !== NULL )
					{
						/* Store the real count so it is not accidentally written as the actual value */
						$realCount = $topic->posts;
						
						/* Compensate for the first post (which is actually the record) */
						$topic->posts = ( $topic->posts - 1 ) > 0 ? $topic->posts - 1 : 0;
						
						/* Get our dash count considering all of that */
						$this->recordCommentDashCount = $topic->commentDashCount();
						
						/* Reset the count back to the real count */
						$topic->posts = $realCount;
					}
					else
					{
						$this->recordCommentDashCount = 0;
					}
				}
				catch( \Exception $e ) { }
			}
			else
			{
				$this->recordCommentDashCount = parent::commentDashCount( $recache );
			}
		}
		
		return $this->recordCommentDashCount;
	}

	/**
	 * Log for deletion later
	 *
	 * \IPS\Member|NULL 	$member	The member or NULL for currently logged in
	 * @return	void
	 */
	public function logDelete( $member = NULL )
	{
		parent::logDelete( $member );

		if ( $topic = $this->topic() and $this->_forum_delete )
		{
			$topic->logDelete( $member );
		}
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		$topic        = $this->topic();
		$commentClass = static::$commentClass;
		
		if ( $this->topic() and $this->_forum_delete )
		{
			$topic->delete();
		}
		else if ( $this->topic() )
		{
			/* We have an attached topic, but we don't want to delete the topic so remove commentClass otherwise we'll delete posts */
			static::$commentClass = NULL;
		}

		/* Remove Record Image And Record Thumb Image */

		if ( $this->record_image )
		{
			try
			{
				\IPS\File::get( 'dcudash_Records', $this->record_image )->delete();
			}
			catch( \Exception $e ){}
		}

		if ( $this->record_image_thumb )
		{
			try
			{
				\IPS\File::get( 'dcudash_Records', $this->record_image_thumb )->delete();
			}
			catch ( \Exception $e ) { }
		}


		/* Remove any reciprocal linking */
		\IPS\Db::i()->delete( 'dcudash_database_fields_reciprocal_map', array( 'map_foreign_item_id=? or map_origin_item_id=?', $this->_id, $this->_id ) );
		
		parent::delete();
		
		if ( $this->topic() )
		{
			static::$commentClass = $commentClass;
		}
	}

	/**
	 * Can view?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( $member=NULL )
	{
		if ( !parent::canView( $member ) )
		{
			return FALSE;
		}

		try
		{
			\IPS\dcudash\Dashes\Dash::loadByDatabaseId( static::database()->id );
		}
		catch( \OutOfRangeException $e )
		{
			/* This prevents auto share and notifications being sent out */
			return FALSE;
		}

		$member = $member ?: \IPS\Member::loggedIn();

		if ( !$this->container()->can_view_others and !$member->modPermission( 'can_content_view_others_records' ) )
		{
			if ( $member != $this->author() )
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/* ! Moderation */
	
	/**
	 * Can edit?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		if ( ( ( static::database()->options['indefinite_own_edit'] AND $member->member_id === $this->member_id ) OR ( $member->member_id and static::database()->all_editable ) ) AND ! $this->locked() AND in_array( $this->hidden(), array(  0, 1 ) ) )
		{
			return TRUE;
		}
		
		if ( parent::canEdit( $member ) )
		{
			/* Test against specific perms for this category */
			return $this->container()->can( 'edit', $member ) or $this->container()->modPermission( 'edit', $member );
		}

		return FALSE;
	}
	
	/**
	 * Can edit title?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEditTitle( $member=NULL )
	{
		if ( $this->canEdit( $member ) )
		{
			try
			{
				$class = '\IPS\dcudash\Fields' .  static::database()->id;
				$field = $class::load( static::database()->field_title );
				return $field->can( 'edit', $member );
			}
			catch( \Exception $e )
			{
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * Can move?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canMove( $member=NULL )
	{
		if ( ! static::database()->use_categories )
		{
			return FALSE;
		}
		
		return parent::canMove( $member );
	}

	/**
	 * Can manage revisions?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	bool
	 * @throws	\BadMethodCallException
	 */
	public function canManageRevisions( \IPS\Member $member = NULL )
	{
		return static::database()->revisions and static::modPermission( 'content_revisions', $member );
	}

	/**
	 * Can comment?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canComment( $member=NULL )
	{
		return ( static::database()->options['comments'] and parent::canComment( $member ) );
	}

	/**
	 * Can review?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canReview( $member=NULL )
	{
		return ( static::database()->options['reviews'] and parent::canReview( $member ) );
	}

	/**
	 * During canCreate() check, verify member can access the module too
	 *
	 * @param	\IPS\Member	$member		The member
	 * @note	The only reason this is abstracted at this time is because Dashes creates dynamic 'modules' with its dynamic records class which do not exist
	 * @return	bool
	 */
	protected static function _canAccessModule( \IPS\Member $member )
	{
		/* Can we access the module */
		return $member->canAccessModule( \IPS\Application\Module::get( static::$application, 'database', 'front' ) );
	}

	/**
	 * Already reviewed?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function hasReviewed( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();

		/* Check cache */
		if( isset( $this->_hasReviewed[ $member->member_id ] ) and $this->_hasReviewed[ $member->member_id ] !== NULL )
		{
			return $this->_hasReviewed[ $member->member_id ];
		}

		$reviewClass = static::$reviewClass;
		$idColumn    = static::$databaseColumnId;

		$this->_hasReviewed[ $member->member_id ] = \IPS\Db::i()->select(
			'COUNT(*)', $reviewClass::$databaseTable, array(
				array(
					$reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'] . '=?',
					$member->member_id
				),
				array(
					$reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?',
					$this->$idColumn
				),
				array( $reviewClass::$databasePrefix . 'database_id=?', static::$customDatabaseId )
			)
		)->first();

		return $this->_hasReviewed[ $member->member_id ];
	}

	/* ! Rating */
	
	/**
	 * Can Rate?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	bool
	 * @throws	\BadMethodCallException
	 */
	public function canRate( \IPS\Member $member = NULL )
	{
		return parent::canRate( $member ) and ( $this->container()->allow_rating );
	}
	
	/* ! Comments */
	/**
	 * Add the comment form elements
	 *
	 * @return	array
	 */
	public function commentFormElements()
	{
		return parent::commentFormElements();
	}

	/**
	 * Add a comment when the filtes changed. If they changed.
	 *
	 * @param   array   $values   Array of new form values
	 * @return  boolean|\IPS\dcudash\Records\Comment
	 */
	public function addCommentWhenFiltersChanged( $values )
	{
		if ( ! $this->canComment() )
		{
			return FALSE;
		}

		$currentValues = $this->fieldValues();
		$commentClass  = 'IPS\dcudash\Records\Comment' . static::$customDatabaseId;
		$categoryClass = 'IPS\dcudash\Categories' . static::$customDatabaseId;
		$fieldsClass   = 'IPS\dcudash\Fields' . static::$customDatabaseId;
		$newValues     = array();
		$fieldsFields  = $fieldsClass::fields( $values, 'edit', $this->category_id ?  $categoryClass::load( $this->category_id ) : NULL, $fieldsClass::FIELD_DISPLAY_COMMENTFORM );

		foreach( $currentValues as $name => $data )
		{
			$id = mb_substr( $name, 6 );
			if ( $id == static::database()->field_title or $id == static::database()->field_content )
			{
				unset( $currentValues[ $name ] );
			}

			/* Not filterable? */
			if ( ! isset( $fieldsFields[ $id ] ) )
			{
				unset( $currentValues[ $name ] );
			}
		}

		foreach( $fieldsFields as $key => $field )
		{
			$newValues[ 'field_' . $key ] = $field::stringValue( isset( $values[ $field->name ] ) ? $values[  $field->name ] : NULL );
		}

		$diff = array_diff_assoc( $currentValues, $newValues );

		if ( count( $diff ) )
		{
			$show    = array();
			$display = $fieldsClass::display( $newValues, NULL, NULL, 'id' );

			foreach( $diff as $name => $value )
			{
				$id = mb_substr( $name, 6 );

				if ( $display[ $id ] )
				{
					$show[ $name ] = sprintf( \IPS\Member::loggedIn()->language()->get( 'dcudash_record_field_changed' ), \IPS\Member::loggedIn()->language()->get( 'content_field_' . $id ), $display[ $id ] );
				}
			}

			if ( count( $show ) )
			{
				$post = \IPS\dcudash\Theme::i()->getTemplate( static::database()->template_display, 'dcudash', 'database' )->filtersAddComment( $show );
				\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $post );
				
				if ( $this->useForumComments() )
				{
					$topic = $this->topic();
					$post  = \IPS\forums\Topic\Post::create( $topic, $post, FALSE );
					
					$commentClass = 'IPS\dcudash\Records\CommentTopicSync' . static::$customDatabaseId;
					
					$comment = $commentClass::load( $post->pid );
					$this->resyncLastComment();

					return $comment;
				}
				else
				{
					return $commentClass::create( $this, $post, FALSE );
				}
			}
		}

		return TRUE;
	}

	/* ! Tags */
	
	/**
	 * Can tag?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	\IPS\Node\Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	bool
	 */
	public static function canTag( \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		return parent::canTag( $member, $container ) and ( static::database()->tags_enabled );
	}
	
	/**
	 * Can use prefixes?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	\IPS\Node\Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	bool
	 */
	public static function canPrefix( \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		return parent::canPrefix( $member, $container ) and ( ! static::database()->tags_noprefixes );
	}
	
	/**
	 * Defined Tags
	 *
	 * @param	\IPS\Node\Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	array
	 */
	public static function definedTags( \IPS\Node\Model $container = NULL )
	{
		if ( static::database()->tags_predefined )
		{
			return explode( ',', static::database()->tags_predefined );
		}
	
		return parent::definedTags( $container );
	}

	/**
	 * Use a custom table helper when building content item tables
	 *
	 * @param	\IPS\Helpers\Table	$table	Table object to modify
	 * @return	\IPS\Helpers\Table
	 */
	public function reputationTableCallback( $table, $currentClass )
	{
		return $table;
	}
	
	/* !Notifications */
	
	/**
	 * Send quote and mention notifications
	 *
	 * @param	array	$exclude		An array of member IDs *not* to send notifications to
	 * @return	array	Member IDs sent to
	 */
	protected function sendQuoteAndMentionNotifications( $exclude=array() )
	{
		$data = array( 'quotes' => array(), 'mentions' => array() );
		
		foreach ( call_user_func( array( 'IPS\dcudash\Fields' .  static::$customDatabaseId, 'data' ) ) as $field )
		{
			if ( $field->type == 'Editor' )
			{
				$key = "field_{$field->id}";
				
				$_data = static::_getQuoteAndMentionIdsFromContent( $this->$key );
				foreach ( $_data as $type => $memberIds )
				{
					$_data[ $type ] = array_filter( $memberIds, function( $memberId ) use ( $field )
					{
						return $field->can( 'view', \IPS\Member::load( $memberId ) );
					} );
				}
				
				$data = array_map( 'array_unique', array_merge_recursive( $data, $_data ) );
			}
		}
		
		return $this->_sendQuoteAndMentionNotifications( $data, $exclude );
	}

    /**
     * Get average review rating
     *
     * @return	int
     */
    public function averageReviewRating()
    {
        if( $this->_averageReviewRating !== NULL )
        {
            return $this->_averageReviewRating;
        }

        $reviewClass = static::$reviewClass;
        $idColumn = static::$databaseColumnId;

        $where = array();
        $where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=? AND review_database_id=?', $this->$idColumn, static::$customDatabaseId );
        if ( in_array( 'IPS\Content\Hideable', class_implements( $reviewClass ) ) )
        {
            if ( isset( $reviewClass::$databaseColumnMap['approved'] ) )
            {
                $where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['approved'] . '=?', 1 );
            }
            elseif ( isset( $reviewClass::$databaseColumnMap['hidden'] ) )
            {
                $where[] = array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['hidden'] . '=?', 0 );
            }
        }

        $this->_averageReviewRating = round( \IPS\Db::i()->select( 'AVG(' . $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] . ')', $reviewClass::$databaseTable, $where )->first(), 1 );

        return $this->_averageReviewRating;
    }

	/**
	 * If, when making a post, we should merge with an existing comment, this method returns the comment to merge with
	 *
	 * @return	\IPS\Content\Comment|NULL
	 */
	public function mergeConcurrentComment()
	{
		$lastComment = parent::mergeConcurrentComment();

		/* If we sync to the forums, make sure that the "last comment" is not actually the first post */
		if( $this->record_topicid AND $lastComment !== NULL )
		{
			$firstComment = \IPS\forums\Topic::load( $this->record_topicid )->comments( 1, 0, 'date', 'asc' );

			if( $firstComment->pid == $lastComment->pid )
			{
				return NULL;
			}
		}

		return $lastComment;
	}
	
	/**
	 * Deletion log Permissions
	 * Usually, this is the same as searchIndexPermissions. However, some applications may restrict searching but
	 * still want to allow delayed deletion log viewing and searching
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function deleteLogPermissions()
	{
		if( ! $this->container()->can_view_others )
		{
			$return = $this->container()->searchIndexPermissions();
			/* If the search index permissions are empty, just return now because no one can see content in this forum */
			if( !$return )
			{
				return $return;
			}

			$return = $this->container()->permissionsThatCanAccessAllRecords();

			if ( $this->member_id )
			{
				$return[] = "m{$this->member_id}";
			}

			return implode( ',', $return );
		}
		
		try
		{
			return parent::searchIndexPermissions();
		}
		catch ( \LogicException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Search Index Permissions
	 * If we don't have a dash, we don't want to add this to the search index
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions()
	{
		/* We don't want to index items in databases with search disabled */
		if( ! static::database()->search )
		{
			return NULL;
		}
		
		return $this->deleteLogPermissions();
	}

	/**
	 * Get output for API
	 *
	 * @param	\IPS\Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse	int						id				ID number
	 * @apiresponse	string					title			Title
	 * @apiresponse	\IPS\dcudash\Categories		category		Category
	 * @apiresponse	object					fields			Field values
	 * @apiresponse	\IPS\Member				author			The member that created the event
	 * @apiresponse	datetime				date			When the record was created
	 * @apiresponse	string					description		Event description
	 * @apiresponse	int						comments		Number of comments
	 * @apiresponse	int						reviews			Number of reviews
	 * @apiresponse	int						views			Number of posts
	 * @apiresponse	string					prefix			The prefix tag, if there is one
	 * @apiresponse	[string]				tags			The tags
	 * @apiresponse	bool					locked			Event is locked
	 * @apiresponse	bool					hidden			Event is hidden
	 * @apiresponse	bool					pinned			Event is pinned
	 * @apiresponse	bool					featured		Event is featured
	 * @apiresponse	string					url				URL
	 * @apiresponse	float					rating			Average Rating
	 * @apiresponse string					image			Record Image
	 * @apiresponse \IPS\forums\Topic		topic			The topic
	 */
	public function apiOutput( \IPS\Member $authorizedMember = NULL )
	{
		return array(
			'id'			=> $this->primary_id_field,
			'title'			=> $this->_title,
			'category'		=> $this->container() ? $this->container()->apiOutput() : null,
			'fields'		=> $this->fieldValues(),
			'author'		=> $this->author()->apiOutput( $authorizedMember ),
			'date'			=> \IPS\DateTime::ts( $this->record_saved )->rfc3339(),
			'description'	=> $this->content(),
			'comments'		=> $this->record_comments,
			'reviews'		=> $this->record_reviews,
			'views'			=> $this->record_views,
			'prefix'		=> $this->prefix(),
			'tags'			=> $this->tags(),
			'locked'		=> (bool) $this->locked(),
			'hidden'		=> (bool) $this->hidden(),
			'pinned'		=> (bool) $this->mapped('pinned'),
			'featured'		=> (bool) $this->mapped('featured'),
			'url'			=> (string) $this->url(),
			'rating'		=> $this->averageRating(),
			'image'			=> $this->record_image ? (string) \IPS\File::get( 'dcudash_Records', $this->record_image )->url : null,
			'topic'			=> $this->topicid ? $this->topic()->apiOutput( $authorizedMember ) : NULL,
		);
	}

	/**
	 * Get items with permission check
	 *
	 * @param	array		$where				Where clause
	 * @param	string		$order				MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array	$limit				Limit clause
	 * @param	string|NULL	$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param	mixed		$includeHiddenItems	Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param	int			$queryFlags			Select bitwise flags
	 * @param	\IPS\Member	$member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly			If true will return the count
	 * @param	array|null	$joins				Additional arbitrary joins for the query
	 * @param	mixed		$skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param	bool		$joinTags			If true, will join the tags table
	 * @param	bool		$joinAuthor			If true, will join the members table for the author
	 * @param	bool		$joinLastCommenter	If true, will join the members table for the last commenter
	 * @param	bool		$showMovedLinks		If true, moved item links are included in the results
	 * @return	\IPS\Patterns\ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( $where=array(), $order=NULL, $limit=10, $permissionKey='read', $includeHiddenItems=\IPS\Content\Hideable::FILTER_AUTOMATIC, $queryFlags=0, \IPS\Member $member=NULL, $joinContainer=FALSE, $joinComments=FALSE, $joinReviews=FALSE, $countOnly=FALSE, $joins=NULL, $skipPermission=FALSE, $joinTags=TRUE, $joinAuthor=TRUE, $joinLastCommenter=TRUE, $showMovedLinks=FALSE )
	{
		
		$where = static::getItemsWithPermissionWhere( $where, $permissionKey, $member, $joinContainer, $skipPermission );
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
	}
	
		/**
	 * WHERE clause for getItemsWithPermission
	 *
	 * @param	array		$where				Current WHERE clause
	 * @param	string		$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param	\IPS\Member	$member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	mixed		$skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip container-based permission. You must still specify this in the $where clause
	 * @return	array
	 */
	public static function getItemsWithPermissionWhere( $where, $permissionKey, $member, &$joinContainer, $skipPermission=FALSE )
	{
		/* Don't show records from categories in which records only show to the poster */
		if ( $skipPermission !== TRUE and in_array( $permissionKey, array( 'view', 'read' ) ) )
		{
			$member = $member ?: \IPS\Member::loggedIn();
			if ( !$member->modPermission( 'can_content_view_others_records' ) )
			{
				if ( $skipPermission instanceof \IPS\dcudash\Categories )
				{
					if ( !$skipPermission->can_view_others )
					{
						$where['item'][] = array( 'dcudash_custom_database_' . static::database()->id . '.member_id=?', $member->member_id );
					}
				}
				else
				{
					$joinContainer = TRUE;

					$where[] = array( '( category_can_view_others=1 OR dcudash_custom_database_' . static::database()->id . '.member_id=? )', $member->member_id );
				}
			}
		}
		
		/* Return */
		return $where;
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType()
	{
		$databaseId = static::database()->_id;
		return "record_id_{$databaseId}";
	}
	
	/**
	 * Supported Meta Data Types
	 *
	 * @return	array
	 */
	public static function supportedMetaDataTypes()
	{
		return array( 'core_FeaturedComments', 'core_ContentMessages' );
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
		return \IPS\Theme::i()->getTemplate( 'global', 'dcudash' )->embedRecord( $this, $this->url()->setQueryString( $params ) );
	}

	/**
	 * Give a content item the opportunity to filter similar content
	 * 
	 * @note Intentionally blank but can be overridden by child classes
	 * @return array|NULL
	 */
	public function similarContentFilter()
	{
		if( $this->record_topicid )
		{
			return array(
				array( '!(tag_meta_app=? and tag_meta_area=? and tag_meta_id=?)', 'forums', 'forums', $this->record_topicid )
			);
		}

		return NULL;
	}
}