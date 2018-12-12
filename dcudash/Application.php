<?php
/**
 * @brief		Dashboard Application Class
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

spl_autoload_register( function( $class )
{
	if ( mb_substr( $class, 0, 15 ) === 'IPS\dcudash\Records' and is_numeric( mb_substr( $class, 15, 1 ) ) )
	{
		$databaseId   = intval( mb_substr( $class, 15 ) );
		$databases    = \IPS\dcudash\Databases::databases();
		
		if ( ! isset( $databases[ $databaseId ] ) )
		{
			return false;
		}
		
		$titleField   = $databases[ $databaseId ]->field_title;
		$contentField = $databases[ $databaseId ]->field_content;
		$contentType  = $databases[ $databaseId ]->key;
		$titleLang    = 'content_db_lang_su_' . $databaseId;
		$includeInSearch = $databases[ $databaseId ]->search ? "TRUE" : "FALSE";

		$data = <<<EOF
		namespace IPS\dcudash;
		class Records{$databaseId} extends Records
		{
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$commentClass = 'IPS\dcudash\Records\Comment{$databaseId}';
			public static \$reviewClass = 'IPS\dcudash\Records\Review{$databaseId}';
			public static \$containerNodeClass = 'IPS\dcudash\Categories{$databaseId}';
			public static \$databaseTable = 'dcudash_custom_database_{$databaseId}';
			public static \$title = '{$titleLang}';
			public static \$module = 'records{$databaseId}';
			public static \$includeInSearch = {$includeInSearch};
			public static \$contentType = '{$contentType}';
			public static \$databaseColumnMap = array(
				'author'				=> 'member_id',
				'container'				=> 'category_id',
				'date'					=> 'record_publish_date',
				'is_future_entry'       => 'record_future_date',
				'future_date'           => 'record_publish_date',
				'title'					=> 'field_{$titleField}',
				'content'				=> 'field_{$contentField}',
				'num_comments'			=> 'record_comments',
				'unapproved_comments'	=> 'record_comments_queued',
				'hidden_comments'		=> 'record_comments_hidden',
				'last_comment'			=> 'record_last_comment',
				'last_comment_by'		=> 'record_last_comment_by',
				'last_comment_name'		=> 'record_last_comment_name',
				'views'					=> 'record_views',
				'approved'				=> 'record_approved',
				'pinned'				=> 'record_pinned',
				'locked'				=> 'record_locked',
				'featured'				=> 'record_featured',
				'observations'			=> 'record_observations',
				'storm_watch'			=> 'record_storm_watch',
				'storm_warning'			=> 'record_storm_warning',
				'active_event'			=> 'record_active_event',
				'rating'				=> 'record_rating',
				'rating_hits'			=> 'rating_hits',
				'rating_average'	    => 'record_rating',
				'rating_total'			=> 'rating_value',
				'num_reviews'	        => 'record_reviews',
				'last_review'	        => 'record_last_review',
				'last_review_by'        => 'record_last_review_by',
				'last_review_name'      => 'record_last_review_name',
				'updated'				=> 'record_last_comment',
				'meta_data'				=> 'record_meta_data'
			);
			public static \$dashPath;
		}
EOF;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 23 ) === 'IPS\dcudash\Records\Comment' and is_numeric( mb_substr( $class, 23, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 23 ) );
	
		$data = <<<EOF
		namespace IPS\dcudash\Records;
		class Comment{$databaseId} extends Comment
		{ 
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$itemClass = 'IPS\dcudash\Records{$databaseId}';
			public static \$title     = 'content_record_comments_title_{$databaseId}';
		}
EOF;
		eval( $data );
	}

	if ( mb_substr( $class, 0, 22 ) === 'IPS\dcudash\Records\Review' and is_numeric( mb_substr( $class, 22, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 22 ) );

		$data = <<<EOF
		namespace IPS\dcudash\Records;
		class Review{$databaseId} extends Review
		{
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$itemClass = 'IPS\dcudash\Records{$databaseId}';
			public static \$title     = 'content_record_reviews_title_{$databaseId}';
		}
EOF;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 18 ) === 'IPS\dcudash\Categories' and is_numeric( mb_substr( $class, 18, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 18 ) );
	
		$data = <<<EOF
		namespace IPS\dcudash;
		class Categories{$databaseId} extends Categories
		{ 
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$contentItemClass = 'IPS\dcudash\Records{$databaseId}';
			protected static \$containerIds = NULL;
			public static \$modPerm = 'dcudash{$databaseId}';
		}
EOF;

		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 32 ) === 'IPS\dcudash\Records\RecordsTopicSync' )
	{
		$databaseId = intval( mb_substr( $class, 32 ) );
	
		$data = <<<EOF
		namespace IPS\dcudash\Records;
		class RecordsTopicSync{$databaseId} extends \IPS\dcudash\Records{$databaseId}
		{ 
			protected static \$multitons = array();
	 		protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$databaseTable = 'dcudash_custom_database_{$databaseId}';
			public static \$databaseColumnId = 'record_topicid';
			public static \$commentClass = 'IPS\dcudash\Records\CommentTopicSync{$databaseId}';

			public function useForumComments()
			{
				return false;
			}
		}
EOF;
		eval( $data );
	}

	if ( mb_substr( $class, 0, 32 ) === 'IPS\dcudash\Records\CommentTopicSync' )
	{
		$databaseId = intval( mb_substr( $class, 32 ) );

		$data = <<<EOF
		namespace IPS\dcudash\Records;
		class CommentTopicSync{$databaseId} extends CommentTopicSync
		{ 
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$itemClass = 'IPS\dcudash\Records\RecordsTopicSync{$databaseId}';
			public static \$title     = 'content_record_comments_title_{$databaseId}';
		}
EOF;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 14 ) === 'IPS\dcudash\Fields' and is_numeric( mb_substr( $class, 14, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 14 ) );
		eval( "namespace IPS\\dcudash; class Fields{$databaseId} extends Fields { public static \$customDatabaseId = $databaseId; }" );
	}

	if ( mb_substr( $class, 0, 47 ) === 'IPS\dcudash\extensions\core\EditorLocations\Records' and is_numeric( mb_substr( $class, 47, 1 ) ) )
	{
		$databaseId = intval( mb_substr( $class, 47 ) );
		eval( "namespace IPS\\dcudash\\extensions\\core\\EditorLocations; class Records{$databaseId} extends \\IPS\\dcudash\\extensions\\core\\EditorLocations\\Records { public static \$customDatabaseId = $databaseId; public static \$buttonLocation	= TRUE; }" );
	}
} );

/**
 * Content Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Returns the ACP Menu JSON for this application.
	 *
	 * @return array
	 */
	public function acpMenu()
	{
		$menu = parent::acpMenu();
		
		if ( ! \IPS\Db::i()->checkForTable('dcudash_databases') )
		{
			return $menu;
		}
		
		/* Now add in the databases... */
		foreach( \IPS\dcudash\Databases::acpMenu() as $database )
		{
			$menu['databases'][ 'records_' . $database['id'] ] = array(
				'tab' 		  => 'dcudash',
				'controller'  => 'records',
				'do' 		  => 'manage&database_id=' . $database['id'],
				'restriction' => 'records_manage'
			);

			if ( $database['use_categories'] )
			{
				$menu['databases'][ 'categories_' . $database['id'] ] = array(
					'tab' 		  => 'dcudash',
					'controller'  => 'categories',
					'do' 		  => 'manage&database_id=' . $database['id'],
					'restriction' => 'categories_manage'
				);
			}
			
			$menu['databases'][ 'fields_' . $database['id'] ] = array(
				'tab' 		  => 'dcudash',
				'controller'  => 'fields',
				'do' 		  => 'manage&database_id=' . $database['id'],
				'restriction' => 'dcudash_fields_manage'
			);
			
			\IPS\Member::loggedIn()->language()->words[ 'menu__dcudash_databases_records_' . $database['id'] ]    = \IPS\Member::loggedIn()->language()->addToStack('content_db_' . $database['id']);
			\IPS\Member::loggedIn()->language()->words[ 'menu__dcudash_databases_categories_' . $database['id'] ] = '&nbsp;&nbsp;' . \IPS\Member::loggedIn()->language()->addToStack('menu__dcudash_categories');
			\IPS\Member::loggedIn()->language()->words[ 'menu__dcudash_databases_fields_' . $database['id'] ]     = '&nbsp;&nbsp;' . \IPS\Member::loggedIn()->language()->addToStack('menu__dcudash_fields');
		}

		return $menu;
	}

	/**
	 * Get Extensions
	 *
	 * @param	\IPS\Application|string		$app		    The app key of the application which owns the extension
	 * @param	string						$extension	    Extension Type
	 * @param	bool						$construct	    Should an object be returned? (If false, just the classname will be returned)
	 * @param	\IPS\Member|bool			$checkAccess	Check access permission for extension against supplied member (or logged in member, if TRUE)
	 * @return	array
	 */
	public function extensions( $app, $extension, $construct=TRUE, $checkAccess=FALSE )
	{		
		$classes = parent::extensions( $app, $extension, $construct, $checkAccess );

		if ( $extension === 'EditorLocations' )
		{
			foreach( \IPS\dcudash\Databases::databases() as $obj )
			{
				$classname = '\\IPS\\dcudash\\extensions\\core\\EditorLocations\\Records' . $obj->_id;

				if ( method_exists( $classname, 'generate' ) )
				{
					$classes = array_merge( $classes, $classname::generate() );
				}
				elseif ( !$construct )
				{
					$classes[ 'Records' . $obj->_id ] = $classname;
				}
				else
				{
					try
					{
						$classes[ 'Records' . $obj->_id ] = new $classname( $checkAccess === TRUE ? \IPS\Member::loggedIn() : ( $checkAccess === FALSE ? NULL : $checkAccess ) );
					}
					catch( \RuntimeException $e ){}
				}
			}
		}

		return $classes;
	}

	/**
	 * Developer sync items
	 *
	 * @param   int     $lastSync       Last time syncd
	 * @return  boolean                 Updated (true), nothing updated(false)
	 */
	public function developerSync( $lastSync )
	{
		$updated = false;

		if ( $lastSync < filemtime( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/databaseschema.json" ) )
		{
			foreach( \IPS\dcudash\Databases::databases() as $key => $db )
			{
				\IPS\dcudash\Databases::checkandFixDatabaseSchema( $db->_id );

				$updated = TRUE;
			}
		}

		return $updated;
	}

	/**
	 * Install 'other' items.
	 *
	 * @return void
	 */
	public function installOther()
	{
		/* Install default database and dash */
		$database = new \IPS\dcudash\Databases;
		$database->key = 'dashboards';
		$database->save();

		/* Add in permissions */
		$groups = array();
		foreach( \IPS\Db::i()->select( 'row_id', 'core_admin_permission_rows', array( 'row_id_type=?', 'group' ) ) as $row )
		{
			$groups[] = $row;
		}

		$default = implode( ',', $groups );

		\IPS\Db::i()->insert( 'core_permission_index', array(
             'app'			=> 'dcudash',
             'perm_type'	=> 'databases',
             'perm_type_id'	=> $database->id,
             'perm_view'	=> '*', # view
             'perm_2'		=> '*', # read
             'perm_3'		=> $default, # add
             'perm_4'		=> $default, # edit
             'perm_5'		=> $default, # reply
             'perm_6'		=> $default  # rate
        ) );

		/* Needs to be added before createDatabase is called */
		\IPS\Lang::saveCustom( 'dcudash', "content_db_" . $database->id, "Dashboards" );
		\IPS\Lang::saveCustom( 'dcudash', "module__dcudash_records" . $database->id, "Dashboards" );
		\IPS\Lang::saveCustom( 'dcudash', "content_db_" . $database->id . '_desc', "Our website dashboards" );
		\IPS\Lang::saveCustom( 'dcudash', "content_db_lang_sl_" . $database->id, 'dashboard' );
		\IPS\Lang::saveCustom( 'dcudash', "content_db_lang_pl_" . $database->id, 'dashboards' );
		\IPS\Lang::saveCustom( 'dcudash', "content_db_lang_su_" . $database->id, 'Dashboard' );
		\IPS\Lang::saveCustom( 'dcudash', "content_db_lang_pu_" . $database->id, 'Dashboards' );
		\IPS\Lang::saveCustom( 'dcudash', "content_db_lang_ia_" . $database->id, 'a dashboard' );
		\IPS\Lang::saveCustom( 'dcudash', "content_db_lang_sl_" . $database->id . '_pl', 'Dashboards' );
		\IPS\Lang::saveCustom( 'dcudash', "dcudash_create_menu_records_" . $database->id, 'Dashboard in Dashboards' );
		\IPS\Lang::saveCustom( 'dcudash', "digest_area_dcudash_records" . $database->id, "Dashboards" );
		\IPS\Lang::saveCustom( 'dcudash', "dcudash_records" . $database->id . '_pl', 'Dashboard' );

		try
		{
			\IPS\dcudash\Databases::createDatabase( $database );
		}
		catch ( \Exception $ex )
		{
			$database->delete();

			\IPS\Log::log( $ex, 'dashes_create_db_error' );

			throw new \LogicException( $ex->getMessage() );
		}

		$database->all_editable = 0;
		$database->revisions    = 1;
		$database->search       = 1;
		$database->comment_bump = 1; # Just new comments bump record
		$database->rss	        = 10;
		$database->record_count = 1;
		$database->fixed_field_perms = array( 'record_image' => array( 'visible' => true, 'perm_view' => '*', 'perm_2' => '*', 'perm_3' => '*' ) );
		$database->options['comments'] = 1;
		$database->field_sort      = 'primary_id_field';
		$database->field_direction = 'asc';
		$database->field_perdash   = 25;
		$database->save();
		
		/* Create default record */
		$item    = 'IPS\dcudash\Records' . $database->id;
		$comment = 'IPS\dcudash\Records\Comment' . $database->id;
		$container = 'IPS\dcudash\Categories' . $database->id;
		
		$link = (string) \IPS\Http\Url::ips('docs/doashboard_docs');

		$content = <<<EOF
<p>Welcome to DCU Dash!</p>
<p>DCU Dashboard extends your site with custom content management and ecommerce capabilites designed especially for communities.
Create brand new user sections of your community using features like blocks, plugins, widgets, layouts, databases and dashboards,
pulling in data from other areas of your member base.</p>
<p>Create custom dashboards using our drag'n'drop, WYSIWYG editor. Use IPS member authentication to create ecommerce apps like
file hosting, server management, web hosting, and anything else you can imagine.
Build blocks that pull in all kinds of data from throughout your client base and community to create dynamic dashboards,
or use one of the ready-made widgets we include with the DCU dashboard application.</p>
<p><br></p>
<p><a href="{$link}">View our DCU Dashboard documentation</a></p>
EOF;
		
		$titleField = 'field_' . $database->field_title;
		$contentField = 'field_' . $database->field_content;
		$category = $container::load( $database->_default_category );
		
		$member = \IPS\Member::loggedIn()->member_id ? \IPS\Member::loggedIn() : \IPS\Member::load(1);
		
		$record = $item::createItem( $member, \IPS\Request::i()->ipAddress(), \IPS\DateTime::ts( time() ), $category, FALSE );
		$record->$titleField = "Welcome to Dashboard";
		$record->$contentField = $content;
		$record->save();

		\IPS\Content\Search\Index::i()->index( $record );
		
		$category->last_record_date = time();
		$category->save();
		
		/* Create the dash */
		$dashValues = array(
			'dash_name'         => "Dashboards",
			'dash_title'        => "Dashboards",
			'dash_seo_name'     => "dashboards.html",
			'dash_folder_id'    => 0,
			'dash_ipb_wrapper'  => 1,
			'dash_show_sidebar' => 1,
			'dash_type'         => 'builder',
			'dash_template'     => 'dash_builder__single_column__dash_dash_builder_single_column'
		);
		
		try
		{
			$dash = \IPS\dcudash\Dashes\Dash::createFromForm( $dashValues, 'html' );
		}
		catch( \Exception $ex )
		{
			\IPS\Log::log( $ex, 'dashes_create_dash_error' );
		}
		
		$dash->setAsDefault();
		
		\IPS\Db::i()->replace( 'core_permission_index', array(
             'app'			=> 'dcudash',
             'perm_type'	=> 'dashes',
             'perm_type_id'	=> $dash->id,
             'perm_view'	=> '*'
        ) );
        
		$database->dash_id = $dash->id;
		$database->save();
		
		unset( \IPS\Data\Store::i()->dashes_dash_urls );
		
		$defaultWidgets = array();
		$buttonUrl      = (string) \IPS\Http\Url::internal( 'applications/dcudash/interface/default/block_arrow.png', 'none' );
		$defaultContent = <<<EOF
		<p>
			<strong>Welcome to DCU Dashboards!</strong>
		</p>
		<p>
			To get started, make sure you are logged in and click the arrow on the left hand side <img src="{$buttonUrl}"> to expand the block manager.
			<br>
			You can move, add and edit blocks without the need for complex coding!
		</p>
EOF;

		/* Default WYSIWYG widget */
		$defaultWidgets[] = array( 
			'app'           => 'dcudash',
			'key'           => 'Wysiwyg',
			'unique'        => mt_rand(),
			'configuration' => array( 'content' => $defaultContent )
		);
		
		/* Default database widget */
		$defaultWidgets[] = array( 
			'app'           => 'dcudash',
			'key'           => 'Database',
			'unique'        => mt_rand(),
			'configuration' => array( 'database' => $database->id )
		);
		
		\IPS\Db::i()->insert( 'dcudash_dash_widget_areas', array(
			'area_dash_id'     => $dash->id,
			'area_widgets'     => json_encode( $defaultWidgets ),
			'area_area'        => 'col1',
			'area_orientation' => 'horizontal'
		) );
						
		/* Add block container (custom)*/
		$container = new \IPS\dcudash\Blocks\Container;
		$container->parent_id = 0;
		$container->name      = "Custom";
		$container->type      = 'block';
		$container->key       = 'block_custom';
		$container->save();

		/* Add block container (plugins) */
		$container = new \IPS\dcudash\Blocks\Container;
		$container->parent_id = 0;
		$container->name      = "Plugins";
		$container->type      = 'block';
		$container->key       = 'block_plugins';
		$container->save();
		
		\IPS\dcudash\Templates::importXml( \IPS\ROOT_PATH . "/applications/dcudash/data/dcudash_theme.xml", NULL, NULL, FALSE );
	}

	/**
	 * Install the application's templates
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	bool		$update	If set to true, do not overwrite current theme setting values
	 * @param	int|null	$offset Offset to begin import from
	 * @param	int|null	$limit	Number of rows to import	
	 * @return	int			Rows inserted
	 */
	public function installTemplates( $update=FALSE, $offset=null, $limit=null )
	{
		$inserted = parent::installTemplates( $update, $offset, $limit );
		
		if ( ( ! $inserted or ( $inserted < $limit ) ) AND $update )
		{
			\IPS\dcudash\Templates::importXml( \IPS\ROOT_PATH . "/applications/dcudash/data/dcudash_theme.xml", NULL, NULL, $update );
		}
	}

	/**
	 * Build skin templates for an app
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function buildThemeTemplates()
	{
		parent::buildThemeTemplates();

		foreach( array( 'database', 'block', 'dash' ) as $location )
		{
			\IPS\dcudash\Theme::importInDev( $location );
		}

		/* Build XML and write to app directory */
		$xml = new \XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );

		/* Root tag */
		$xml->startElement('theme');
		$xml->startAttribute('name');
		$xml->text( "Default" );
		$xml->endAttribute();
		$xml->startAttribute('author_name');
		$xml->text( "devCU Software" );
		$xml->endAttribute();
		$xml->startAttribute('author_url');
		$xml->text( "https://www.devcu.com" );
		$xml->endAttribute();

		/* Templates */
		foreach ( \IPS\Db::i()->select( '*', 'dcudash_templates', array( 'template_master=1 and template_user_created=0 and template_user_edited=0' ), 'template_group, template_title' ) as $template )
		{
			/* Initiate the <template> tag */
			$xml->startElement('template');

			foreach( $template as $k => $v )
			{
				if ( in_array( \substr( $k, 9 ), array('key', 'title', 'desc', 'location', 'group', 'params', 'app', 'type' ) ) )
				{
					$xml->startAttribute( $k );
					$xml->text( $v );
					$xml->endAttribute();
				}
			}

			/* Write value */
			if ( preg_match( '/<|>|&/', $template['template_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $template['template_content'] ) );
			}
			else
			{
				$xml->text( $template['template_content'] );
			}

			/* Close the <template> tag */
			$xml->endElement();
		}

		/* Finish */
		$xml->endDocument();

		/* Write it */
		if ( is_writable( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/dcudash_theme.xml', $xml->outputMemory() );
		}
		else
		{
			throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}

	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'tachometer';
	}

	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		$browseTabs = array();
		
		try
		{
			$defaultDash = \IPS\dcudash\Dashes\Dash::getDefaultDash();
			$browseTabs[] = array( 'key' => 'Dashes', 'config' => array( 'menu_content_dash' => $defaultDash->id, 'menu_title_dash_type' => 0 ) );
		}
		catch( \OutOfRangeException $ex ) { }
		
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> $browseTabs,
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
}