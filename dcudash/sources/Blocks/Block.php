<?php
/**
 * @brief		Block Model
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\Blocks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Block Model
 */
class _Block extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'dcudash_blocks';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'block_';

	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array('block_key');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();

	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = null;

	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static $parentNodeColumnId = 'category';

	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static $parentNodeClass = 'IPS\dcudash\Blocks\Container';

	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;

	/**
	 * @brief	[Node] Sortable?
	 */
	public static $nodeSortable = TRUE;

	/**
	 * @brief	[Node] Title
	 */
	public static $nodeTitle = 'block';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'content_block_name_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static $descriptionLangSuffix = '_desc';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 array(
	 'app'		=> 'core',				// The application key which holds the restrictrions
	 'module'	=> 'foo',				// The module key which holds the restrictions
	 'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 'add'			=> 'foo_add',
	 'edit'			=> 'foo_edit',
	 'permissions'	=> 'foo_perms',
	 'delete'		=> 'foo_delete'
	 ),
	 'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
			'app'		=> 'dcudash',
			'module'	=> 'dashes',
			'prefix' 	=> 'block_'
	);

	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'dcudash';

	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'blocks';

	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
			'view' => 'view'
	);

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_dcudash_block_';

	/**
	 * @brief  Templates already loaded and evald via getTemplate()
	 */
	public static $calledTemplates = array();

	/**
	 * Parse a block for display
	 * Wrapped in a static method so we can catch the OutOfRangeException and take action.
	 *
	 * @param	string|int|\IPS\dcudash\Blocks\Block	$block	Block ID
     * @param	string	$orientation	Orientation
	 * @return	string	Ready to display HTML
	 */
	public static function display( $block, $orientation=NULL )
	{
		try
		{
			try
			{
				if ( is_numeric( $block ) )
				{
					$block = static::load( $block );
				}
				else if ( ! $block instanceof \IPS\dcudash\Blocks\Block )
				{
					$block = static::load( $block, 'block_key' );
				}

				if ( !$block->active )
				{
					return NULL;
				}
			}
			catch( \OutOfRangeException $ex )
			{
				return NULL;
			}

			/* We gots the perms to see this? */
			if ( !$block->can( 'view' ) )
			{
				return NULL;
			}

			if ( $block->type === 'custom' )
			{
				try
				{
					$functionName = 'content_blocks_' .  $block->id;
	
					if ( ! isset( \IPS\Data\Store::i()->$functionName ) )
					{
						$content = $block->content;
	
						if( $block->getConfig('editor') == 'php' )
						{
							ob_start();
							eval( $content );
							$content = ob_get_clean();
						}
	
						\IPS\Data\Store::i()->$functionName = \IPS\Theme::compileTemplate( $content, $functionName, null, true );
					}
	
					\IPS\Theme::runProcessFunction( \IPS\Data\Store::i()->$functionName, $functionName );
	
					if( $block->getConfig('editor') == 'php' )
					{
						unset( \IPS\Data\Store::i()->$functionName );
					}

					$html = call_user_func( 'IPS\\Theme\\'. $functionName );

					if( $block->getConfig('editor') == 'editor' )
					{
						$html = \IPS\Theme::i()->getTemplate( 'widgets', 'dcudash', 'front' )->Wysiwyg( $html, $orientation );
					}

					return $html;
				}
				catch ( \ParseError $e )
				{
					@ob_end_clean();
					\IPS\Log::log( $e, 'block_error' );
					return "<span style='background:black;color:white;padding:6px;'>[[Block {$block->key} is throwing an error]]</span>";
				}
			}
			else
			{
				$block->orientation = $orientation;

				if ( $block->template OR $block->content )
				{
					$block->widget()->template( array( $block, 'getTemplate' ) );
				}

				return $block->widget()->render();
			}
		}
		catch( \OutOfRangeException $ex )
		{
			return NULL;
		}
	}

	/**
	 *  Method to overload standard widget templates
	 *
	 *  @return void
	 */
	public function getTemplate()
	{
		$args		  = func_get_args();
		$functionName = 'content_template_for_block_' .  $this->id;

		unset( \IPS\Data\Store::i()->$functionName );

		/* Still here */
		if ( ! in_array( $functionName, array_keys( static::$calledTemplates ) ) )
		{
			if ( ! isset( \IPS\Data\Store::i()->$functionName ) )
			{
				if ( $this->content )
				{
					\IPS\Data\Store::i()->$functionName = \IPS\Theme::compileTemplate( $this->content, 'run', $this->template_params, true );
					
				}
				else if ( $this->template )
				{
					try
					{
						$template	= \IPS\dcudash\Templates::load( $this->template );
						$object		= \IPS\dcudash\Theme::i()->getTemplate( $template->group, 'dcudash', $template->location );
						$title		= $template->title;
						return $object->$title( ...$args );
					}
					catch( \OutOfRangeException $ex )
					{
						/* @todo what to do here? */
					}
				}
			}

			/* Put them in a class */
			$template = <<<EOF
class class_{$functionName}
{

EOF;
			$template .= \IPS\Data\Store::i()->$functionName;

			$template .= <<<EOF
}
EOF;

			/* It lives! */
			\IPS\Theme::runProcessFunction( $template, $functionName );

			$class = "\IPS\Theme\\class_{$functionName}";

			/* Init */
			static::$calledTemplates[ $functionName ] = new $class();
		}
		
		return static::$calledTemplates[ $functionName ]->run( ...$args );
	}

	/**
	 * Delete compiled versions
	 *
	 * @param 	null|int|array 	$ids	Integer ID or Array IDs to remove
	 * @return void
	 */
	public static function deleteCompiled( $ids=NULL )
	{
		if ( $ids === NULL )
		{
			$ids = iterator_to_array( \IPS\Db::i()->select( 'block_id', 'dcudash_blocks' )->setValueField('block_id') );
		}
		else if ( is_numeric( $ids ) )
		{
			$ids = array( $ids );
		}

		foreach( $ids as $id )
		{
			$functionName = 'content_blocks_' .  $id;
			if ( isset( \IPS\Data\Store::i()->$functionName ) )
			{
				unset( \IPS\Data\Store::i()->$functionName );
			}

			$functionName = 'content_template_for_block_' .  $id;
			if ( isset( \IPS\Data\Store::i()->$functionName ) )
			{
				unset( \IPS\Data\Store::i()->$functionName );
			}
		}
		
		/* We can also use blocks in per-dash CSS */
		\IPS\dcudash\Dashes\Dash::deleteCompiledIncludes();
	}

	/**
	 * @brief	Config json as array
	 */
	protected $_config = null;

	/**
	 * @brief   Stores a \IPS\Widget object if this is a custom block with an embedded widget
	 */
	protected $widgetLoaded = NULL;

	/**
	 * @brief   Orientation for an embedded widget
	 */
	public $orientation = NULL;

	/**
	 * Get config as an array if no $key, or as whatever type corresponds to key
	 *
	 * @param	string|null	$key	Config key to fetch
	 * @return	mixed
	 */
	public function getConfig( $key = NULL)
	{
		if ( $this->_config === NULL )
		{
			$this->_config = json_decode( $this->config, TRUE );

			if ( $this->_config === FALSE )
			{
				$this->_config = array();
			}
		}

		if ( $key )
		{
			if ( isset( $this->_config[ $key ] ) )
			{
				return $this->_config[ $key ];
			}

			return NULL;
		}

		return $this->_config;
	}

	/**
	 * Set config key and value
	 *
	 * @param	string	$key	Config key
	 * @param	mixed	$value	Config value
	 * @return	mixed
	 */
	public function setConfig( $key, $value )
	{
		$this->_config[ $key ] = $value;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge()
	{
		return array(
			0	=> 'ipsBadge ipsBadge_intermediary ipsPos_right',
			1	=> $this->type === 'custom' ? 'content_block_add_type_custom' : 'content_block_add_type_plugin',
		);
	}

	/**
	 * [Node] Get description
	 *
	 * @return	string
	 */
	protected function get__description()
	{
		return \IPS\Member::loggedIn()->language()->addToStack( 'content_block_name_' . $this->_id . '_desc' );
	}

	/**
	 * Get configuration.
	 *
	 * @return array
	 */
	public function get__plugin_config()
	{
		return ( $this->plugin_config ? ( is_array( $this->plugin_config ) ? $this->plugin_config : json_decode( $this->plugin_config, TRUE ) ) : array() );
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );

		if ( isset( $buttons['add'] ) )
		{
			unset( $buttons['add']['data'] );
		}

		if ( isset( $buttons['edit'] ) )
		{
			unset( $buttons['edit']['data'] );
		}

        /* View Details */
        $buttons['details']	= array(
            'icon'	=> 'search',
            'title'	=> 'block_embed_options',
            'link'	=> \IPS\Http\Url::internal( "app=dcudash&module=dashes&controller=blocks&do=embedOptions&id={$this->_id}" ),
            'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('block_embed_options') )
        );

		return $buttons;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$block_type   = ( isset( \IPS\Request::i()->block_type ) )   ? \IPS\Request::i()->block_type   : ( $this->id ? $this->type : null );
		$block_editor = ( isset( \IPS\Request::i()->block_editor ) ) ? \IPS\Request::i()->block_editor : ( $this->id ? $this->getConfig('editor') : null );
		$block_plugin = ( isset( \IPS\Request::i()->block_plugin ) ) ? \IPS\Request::i()->block_plugin : ( $this->id ? $this->plugin : null );

		/* Build form */
		$form->addTab( 'content_block_form_tab__details' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'block_name', NULL, TRUE, array(
				'app'  => 'dcudash',
				'key'  => ( $this->id ? "content_block_name_" .  $this->id : NULL )
		) ) );

		$form->add( new \IPS\Helpers\Form\Translatable( 'block_description', NULL, FALSE, array(
            'app' => 'dcudash',
            'key' => ( $this->id ? "content_block_name_" .  $this->id . '_desc' : NULL )
        ) ) );

		$nodeContainer = $this->id ? $this->category :
			( \IPS\Request::i()->parent ?: \IPS\dcudash\Blocks\Container::load( ( $block_type == 'custom' ? 'block_custom' : 'block_plugins' ), 'container_key' )->id );
		$form->add( new \IPS\Helpers\Form\Node( 'block_category', $nodeContainer, TRUE, array(
				'class'    => '\IPS\dcudash\Blocks\Container',
				'subnodes' => false
		) ) );

		$form->addHeader( 'dcudash_block_form_display' );

		$form->add( new \IPS\Helpers\Form\Text( 'block_key', $this->id ? $this->key : FALSE, FALSE, array(), function( $val )
		{
			try
			{
				if ( ! $val )
				{
					return true;
				}

				try
				{
					$block = \IPS\dcudash\Blocks\Block::load( $val, 'block_key');
				}
				catch( \OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
					return true;
				}

				/* It's taken... */
				if ( \IPS\Request::i()->id == $block->id )
				{
					/* But it's this one so that's ok */
					return true;
				}

				/* and if we're here, it's not... */
				throw new \InvalidArgumentException('dcudash_block_key_not_unique');
			}
			catch ( \OutOfRangeException $e )
			{
				/* Slug is OK as load failed */
				return true;
			}

			return true;
		} ) );

		/* Do we have config? */
		if ( $block_type === 'plugin' and $block_plugin )
		{
			if ( ! $this->id )
			{
				$this->type       = 'plugin';
				$this->plugin     = $block_plugin;
				
				if ( isset( \IPS\Request::i()->block_plugin_app ) )
				{
					$this->plugin_app = \IPS\Request::i()->block_plugin_app;
				}
				elseif ( isset( \IPS\Request::i()->block_plugin_plugin ) )
				{
					$this->plugin_plugin = \IPS\Request::i()->block_plugin_plugin;
				}
			}

			if ( mb_substr( $block_plugin, 0, 8 ) === 'db_feed_' )
			{
				$databaseId = intval( mb_substr( $block_plugin, 8 ) );
				$this->plugin = 'RecordFeed';
				$this->plugin_config = array( 'dcudash_rf_database' => $databaseId );

				/* JS needs this to produce the preview */
				$form->hiddenValues['dcudash_rf_database'] = $databaseId;
			}
			else if ( $this->id and $block_plugin === 'RecordFeed' )
			{
				$form->hiddenValues['dcudash_rf_database'] = $this->_plugin_config['dcudash_rf_database'];
			}
						
			try
			{
				$block_editor = 'html';

				\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_block_feed_form_message', FALSE, array( 'sprintf' => $this->widget()->title() ) ), 'information', NULL, FALSE );
			}
			catch ( \OutOfRangeException $ex )
			{
				throw new \LogicException( 'dcudash_error_block_plugin_not_found' );
			}

			if ( is_callable( array( $this->widget(), 'configuration' ) ) )
			{
				$form->addTab( 'content_block_form_tab__feed' );
				$this->widget()->configuration( $form );
			}
		}

		$form->addTab( 'content_block_form_tab__content');

		if ( $block_type === 'plugin' )
		{
			$form->addHtml( \IPS\Theme::i()->getTemplate( 'blocks', 'dcudash', 'admin' )->previewBlock() );

			$templates = array( '_default_' => \IPS\Member::loggedIn()->language()->addToStack('content_block_template_use_default') );

			foreach( \IPS\dcudash\Templates::getTemplates( \IPS\dcudash\Templates::RETURN_BLOCK ) as $id => $obj )
			{
				if ( $obj->group == $this->widget()->key )
				{
					$templates[ $obj->key ] = $obj->title;
				}
			}

			/* List of templates */
			$form->add( new \IPS\Helpers\Form\Select( 'block_template_id', ( $this->id and $this->template ) ? $this->template : NULL, FALSE, array(
					'options' => $templates
			), NULL, NULL, \IPS\Theme::i()->getTemplate( 'blocks', 'dcudash', 'admin' )->previewTemplateLink( $block_plugin ), 'block_template_id' ) );

			/* Use or copy to edit */
			$useHow = NULL;
			if ( $this->id )
			{
				if ( intval( $this->template ) or ( ! intval( $this->template ) and ! $this->content ) )
				{
					$useHow = 'use';
				}
				else
				{
					$useHow = 'copy';
				}
			}

			$form->add( new \IPS\Helpers\Form\Select( 'block_template_use_how', $useHow, FALSE, array(
					'options' => array(
						'use'	=> 	'block_template_use_how_use',
						'copy'	=>  'block_template_use_how_copy'
					),
					'toggles' => array(
						'copy' => array( 'block_content', 'block_save_as_template' )
					)
			), NULL, NULL, NULL, 'block_template_use_how' ) );
		}

		if ( $block_editor === 'editor' )
		{
			$form->add( new \IPS\Helpers\Form\Editor( 'block_content', $this->content, FALSE, array(
				'app'         => 'dcudash',
				'key'         => 'BlockContent',
				'autoSaveKey' => 'block-content-' . ( $this->id ? $this->id : 'new' ),
				'attachIds'	  => ( $this->id ) ? array( $this->id ) : NULL ), NULL, NULL, NULL, 'block_content_editor' ) );
		}
		else
		{
			$form->add( new \IPS\Helpers\Form\Codemirror( 'block_content', htmlentities( $this->content, ENT_DISALLOWED, 'UTF-8', TRUE ), FALSE, array(), function( $val ) {
				if ( \IPS\Request::i()->block_editor == 'php' )
				{
					try
					{
						ob_start();
						@eval( $val );
						ob_get_clean();
					}
					catch ( \Exception $e )
					{
						throw new \DomainException( $e->getMessage() );
					}
				}
				
				if ( mb_strpos( $val, '{block="' . \IPS\Request::i()->block_key . '"}' ) !== FALSE )
				{
					throw new \DomainException('block_content_recursive_error');
				}
			}, NULL, NULL, 'block_content' ) );
		}

		if ( $block_type === 'plugin' and ! $this->id )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'block_save_as_template', FALSE, FALSE, array(
				'togglesOn' => array( 'block_save_as_template_name' )
			), NULL, NULL, NULL, 'block_save_as_template' ) );

			$form->add( new \IPS\Helpers\Form\Text( 'block_save_as_template_name', NULL, FALSE, array(), NULL, NULL, NULL, 'block_save_as_template_name' ) );
		}

		if ( $block_type === 'custom' )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'block_cache', ( $this->id ) ? $this->cache : FALSE, FALSE, array(), NULL, NULL, NULL, 'block_cache' ) );
		}

		$form->hiddenValues['block_type']      = $block_type;
		$form->hiddenValues['block_editor']    = $block_editor;
		$form->hiddenValues['block_plugin']    = $this->plugin;
		if ( $this->plugin_app )
		{
			$form->hiddenValues['block_plugin_app']= $this->plugin_app;
		}
		if ( $this->plugin_plugin )
		{
			$form->hiddenValues['block_plugin_plugin']= $this->plugin_plugin;
		}
		$form->hiddenValues['template_params'] = ( $this->id ) ? $this->template_params : '';

		/* If we are editing, we can save and reload */
		if( $this->id )
		{
			$form->canSaveAndReload = true;
		}

		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'templates/view.css', 'dcudash', 'admin' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'blocks/form.css', 'dcudash', 'admin' ) );

		\IPS\Output::i()->globalControllers[]  = 'dcudash.admin.blocks.form';
		\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_blocks.js', 'dcudash' ) );
		\IPS\Output::i()->title = ( $this->id ) ? \IPS\Member::loggedIn()->language()->addToStack('content_block_block_editing', NULL, array( 'sprintf' => array( $this->_title ) ) ) : \IPS\Member::loggedIn()->language()->addToStack('content_block_block_add');
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		/* Claim Attachments - we need to adjust the temp key based on presence if this block existed or not */
		if ( \IPS\Request::i()->block_editor === 'editor' )
		{
			\IPS\File::claimAttachments( ( $this->id ) ? 'block-content-' . $this->id : 'block-content-new', $this->id );
		}
		
		if ( ! $this->id )
		{
			$this->active = 1;
			$this->save();
		}

		$config = array();
		if( isset( $values['language_key'] ) )
		{
			$config['language_key'] = $values['language_key'];
		}

		if( isset( $values['block_name'] ) )
		{
			\IPS\Lang::saveCustom( 'dcudash', "content_block_name_" . $this->id, $values['block_name'] );
		}

		if( isset( $values['block_description'] ) )
		{
			\IPS\Lang::saveCustom( 'dcudash', "content_block_name_" . $this->id . '_desc', $values['block_description'] );

			unset ( $values['block_description'] );
		}

		$this->type		= \IPS\Request::i()->block_type;
		$values['type']	= $this->type;

		if( isset( $values['block_key'] ) )
		{
			if ( ! $values['block_key'] )
			{
				if ( is_array( $values['block_name'] ) )
				{
					reset( $values['block_name'] );
					$values['block_key'] = \IPS\Http\Url\Friendly::seoTitle( $values['block_name'][ key( $values['block_name'] ) ] );
				}
				else
				{
					$values['block_key'] = \IPS\Http\Url\Friendly::seoTitle( $values['block_name'] );
				}

				/* Now test it */
				try
				{
					$block = \IPS\dcudash\Blocks\Block::load( $this->key, 'block_key');

					/* It's taken... */
					if ( $this->id != $block->id )
					{
						$values['block_key'] .= '_' . mt_rand();
					}
				}
				catch( \OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
				}
			}
		}

		if ( \IPS\Request::i()->block_type === 'plugin' )
		{
			$values['plugin_app'] = \IPS\Request::i()->block_plugin_app;

			/* configure widget related values */
			if ( is_callable( array( $this->widget(), 'preConfig' ) ) )
			{
				$values = $this->widget()->preConfig( $values );
			}

			/* Store config */
			foreach( $values as $k => $v )
			{
				if ( ! in_array( $k, array( 'block_name', 'block_key', 'block_description', 'block_category', 'block_template_id', 'block_template_use_how', 'block_content', 'block_save_as_template', 'block_save_as_template_name' ) ) )
				{
					if ( is_array( $v ) )
					{
						$theValue = NULL;
						foreach( $v as $eachKey => $eachValue )
						{
							if ( !( $eachValue instanceof \IPS\Node\Model ) AND $eachValue instanceof \IPS\Patterns\ActiveRecord )
							{
								$column     = $eachValue::$databaseColumnId;
								$theValue[] = $eachValue->$column;
							}
							elseif( $eachValue instanceof \IPS\Node\Model )
							{
								$theValue[ $eachKey ] = $eachValue;
							}
							elseif ( $eachKey === 'start' or $eachKey === 'end' )
							{
								/* date ranges */
								$theValue[ $eachKey ] = $eachValue;
							}
							else
							{
								$theValue[ $eachKey ] = $eachValue;
							}
						}
						$v = $theValue;
					}
					else if ( !( $v instanceof \IPS\Node\Model ) AND $v instanceof \IPS\Patterns\ActiveRecord )
					{
						$column = $v::$databaseColumnId;
						$v      = $v->$column;
					}
					else if ( $v instanceof \IPS\Http\Url )
					{
						$v = (string) $v;
					}

					$config[ $k ] = $v;

					unset( $values[ $k ] );
				}
			}

			$values['plugin']			= \IPS\Request::i()->block_plugin;
			$values['plugin_config']	= json_encode( $config );

			/* Are we using the template as-is? */
			if ( $values['block_template_use_how'] === 'use' )
			{
				$values['content'] = null;

				/* Not using default? */
				if ( $values['block_template_id'] != '_default_' )
				{
					$values['template'] = $values['block_template_id'];
				}
				else
				{
					$values['template'] = 0;
				}
			}
			else
			{
				/* We're using a copy */
				if ( isset( $values['block_save_as_template'] ) AND $values['block_save_as_template'] )
				{
					if ( $values['block_template_id'] == '_default_' )
					{
						/* Find it from the normal template system */
						$plugin = $this->widget();

						$location = $plugin->getTemplateLocation();

						$templateBits  = \IPS\Theme::master()->getRawTemplates( $location['app'], $location['location'], $location['group'], \IPS\Theme::RETURN_ALL );
						$templateBit   = $templateBits[ $location['app'] ][ $location['location'] ][ $location['group'] ][ $location['name'] ];

						$templateArray = array(
							'key' 		   => 'template_' . $templateBit['template_name'] . '.' . mt_rand(),
							'title'		   => str_replace( '-', '_', \IPS\Http\Url\Friendly::seoTitle( $values['block_save_as_template_name'] ? $values['block_save_as_template_name'] : $templateBit['template_name'] . '_' . \IPS\Member::loggedIn()->language()->get('copy_noun') ) ),
							'desc' 		   => null,
							'content' 	   => $values['block_content'],
							'location' 	   => 'block',
							'group' 	   => \IPS\Request::i()->block_plugin,
							'container'    => null,
							'rel_id' 	   => 0,
							'user_created' => 1,
							'user_edited'  => 0,
							'params'  	   => $templateBit['template_data']
						);
					}
					else
					{
						try
						{
							$template = \IPS\dcudash\Templates::load( $values['block_template_id'] );

							$templateArray = array(
								'key' 		   => 'template_' . $template->name . '.' . mt_rand(),
								'title'		   => str_replace( '-', '_', \IPS\Http\Url\Friendly::seoTitle( $values['block_save_as_template_name'] ? $values['block_save_as_template_name'] : $template->name . '_' . \IPS\Member::loggedIn()->language()->get('copy_noun') ) ),
								'desc' 		   => null,
								'content' 	   => $values['block_content'],
								'location' 	   => 'block',
								'group' 	   => \IPS\Request::i()->block_plugin,
								'container'    => null,
								'rel_id' 	   => 0,
								'user_created' => 1,
								'user_edited'  => 0,
								'params'  	   => $template->params
							);
						}
						catch( \OutOfRangeException $ex )
						{
							throw new \LogicException('dcudash_error_no_template_found');
						}
					}

					/* Save */
					$newTemplate = \IPS\dcudash\Templates::add( $templateArray );

					$values['content']  = null;
					$values['template'] = $newTemplate->id;
				}
				else
				{
					/* Just use it this once */
					$values['content']  = $values['block_content'];
					$values['template'] = 0;
				}
			}
		}
		else if( isset( $values['block_content'] ) )
		{
			$values['template'] = 0;
		}

		if ( isset( $values['block_category'] ) AND ( ! empty( $values['block_category'] ) OR $values['block_category'] === 0 ) )
		{
			$values['block_category'] = ( $values['block_category'] === 0 ) ? 0 : $values['block_category'];

			if( isset( $values['block_category'] ) AND $values['block_category'] instanceof \IPS\Node\Model )
			{
				$values['block_category']	= $values['block_category']->_id;
			}
		}

		if( isset( \IPS\Request::i()->template_params ) )
		{
			$values['template_params'] = \IPS\Request::i()->template_params;
		}

		/* Config */
		if( isset( \IPS\Request::i()->block_editor ) )
		{
			$this->setConfig( 'editor', \IPS\Request::i()->block_editor );
		}

		foreach( array( 'block_name', 'block_description', 'block_save_as_template', 'block_template_id', 'block_template_use_how', 'block_save_as_template_name', 'block_editor', 'block_plugin_app', 'block_plugin' ) as $field )
		{
			if ( array_key_exists( $field, $values ) )
			{
				unset( $values[ $field ] );
			}
		}

		return $values;
	}

	/**
	 * Save data
	 *
	 * @return void
	 */
	public function save()
	{
		if ( $this->_config !== NULL )
		{
			$this->config = json_encode( $this->_config );
		}

		if ( $this->id )
		{
			static::deleteCompiled( $this->id );
			\IPS\dcudash\Widget::deleteCachesForBlocks( $this->key );
		}

		parent::save();
	}

	/**
	 * Returns the widget object associated with this custom block
	 *
	 * @return \IPS\Widget
	 */
	public function widget()
	{
		if ( $this->type === 'plugin' AND $this->plugin )
		{
			if ( mb_substr( $this->plugin, 0, 8 ) === 'db_feed_' )
			{
				$this->plugin = 'RecordFeed';
			}

			if ( ! $this->widgetLoaded )
			{
				$this->widgetLoaded = \IPS\Widget::load( $this->plugin_app ? \IPS\Application::load( $this->plugin_app ) : \IPS\Plugin::load( $this->plugin_plugin ), $this->plugin, mt_rand(), $this->_plugin_config, NULL, $this->orientation );
			}

			return $this->widgetLoaded;
		}

		throw new \OutOfRangeException;
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		parent::__clone();

		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}
		
		/* Copy language bits to make them unique */
		$config = $this->_plugin_config;
		$wordKey = 'widget_title_' . md5( mt_rand() );
		
		if ( isset( $config['language_key'] ) )
		{
			try
			{
				foreach( \IPS\Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', $config['language_key'] ) ) as $row )
				{
					unset( $row['word_id'] );
					$row['word_key'] = $wordKey;
					\IPS\Db::i()->insert( 'core_sys_lang_words', $row );
				}
			}
			catch( \Exception $ex ) { }
			
			$config['language_key'] = $wordKey;
			$this->plugin_config = json_encode( $config );
		}
		
		$this->key .= '_' . mt_rand();
		$this->save();
	}
}
