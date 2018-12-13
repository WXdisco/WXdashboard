<?php
/**
 * @brief		Block Controller
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\modules\admin\dashes;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * blocks
 */
class _blocks extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\dcudash\Blocks\Container';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'block_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 * 
	 * @return	void
	 */
	public function manage()
	{
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__dcudash_dashes_blocks');
		$nodeClass = $this->nodeClass;
		
		if ( $nodeClass::canAddRoot() )
		{
			\IPS\Output::i()->sidebar['actions']['add_meow'] = array(
				'primary'	=> true,
				'icon'	=> 'plus',
				'title'	=> 'content_block_cat_add',
				'link'	=> $this->url->setQueryString( 'do', 'form' ),
				'data'	=> ( $nodeClass::$modalForms ? array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_block_cat_add') ) : array() )
			);
		}
	
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'dcudash', 'dashes', 'block_add' ) )
		{
			\IPS\Output::i()->sidebar['actions']['add_block'] = array(
				'primary'	=> true,
				'icon'	=> 'puzzle-piece',
				'title'	=> 'content_block_block_add',
				'link'	=> $this->url->setQueryString( array( 'do' => 'addBlockType', 'subnode' => 1 ) ),
				'data'	=> ( $nodeClass::$modalForms ? array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_block_block_add') ) : array() )
			);
		}
		parent::manage();
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		if ( isset( \IPS\Request::i()->id ) )
		{
			\IPS\dcudash\Blocks\Block::deleteCompiled( \IPS\Request::i()->id );
		}
		
		parent::delete();
	}
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$nodeClass = $this->nodeClass;
		$buttons   = array();
		
		return $buttons;
	}
	
	/**
	 * Add block, pre form
	 * 
	 * @return void
	 */
	public function addBlockType()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'block_add' );
			
		$form = new \IPS\Helpers\Form( 'block_add_first_step', 'next');
		
		$form->add( new \IPS\Helpers\Form\Radio( 'content_block_add_type', 'plugin', FALSE, array(
				'options' => array(
						'plugin'  => 'content_block_add_type_plugin',
						'custom'  => 'content_block_add_type_custom'
				),
				'toggles' => array(
						'plugin' => array( 'content_block_add_type_plugin' ),
						'custom' => array( 'content_block_add_custom_type' )
				)
			)
		) );

		$plugins = array();
		foreach ( \IPS\Db::i()->select( "*", 'core_widgets', array( 'embeddable=1') ) as $widget )
		{
			try
			{
				if ( $widget['app'] )
				{
					$app = \IPS\Application::load( $widget['app'] );
					if ( $app->enabled )
					{
						$plugins[ $app->_title ][ 'app__' . $widget['app'] . '__' . $widget['key'] ] = \IPS\Member::loggedIn()->language()->addToStack( 'block_' . $widget['key'] );
					}

				}
				else
				{
					$plugin = \IPS\Plugin::load( $widget['plugin'] );
					if ( $plugin->enabled )
					{
						$plugins[ $plugin->_title ][ 'plugin__' . $widget['plugin'] . '__' . $widget['key'] ] = \IPS\Member::loggedIn()->language()->addToStack( 'block_' . $widget['key'] );
					}
				}
			}
			catch ( \UnexpectedValueException $e ) { }
			catch ( \OutOfRangeException $e ) { }
		}
		
		$disabled = array();
		foreach( \IPS\dcudash\Databases::databases() as $db )
		{
			if ( $db->dash_id )
			{
				$plugins[ \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_db_feed_title' ) ][ 'db_feed_' . $db->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_db_feed_block_with_name', NULL, array( 'sprintf' => array( $db->_title ) ) );
			}
			else
			{
				$disabled[] = 'db_feed_' . $db->id;
				$plugins[ \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_db_feed_title' ) ][ 'db_feed_' . $db->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_db_feed_block_with_name_disabled', NULL, array( 'sprintf' => array( $db->_title ) ) );
			}
		}

		$form->add( new \IPS\Helpers\Form\Select( 'content_block_add_type_plugin', null, false, array( 'options' => $plugins, 'disabled' => $disabled ), NULL, NULL, NULL, 'content_block_add_type_plugin' ) );

		$form->add( new \IPS\Helpers\Form\Radio( 'content_block_add_custom_type', 'editor', TRUE, array(
				'options' => array( 'editor'	=> 'content_block_add_custom_type_wysiwyg',
									'html'      => 'content_block_add_custom_type_html',
									'php'		=> 'content_block_add_custom_type_php'
		) ), NULL, NULL, NULL, 'content_block_add_custom_type' ) );
		
		if ( $values = $form->values() )
		{
			if ( $values['content_block_add_type'] !== 'custom' )
			{
				if ( mb_substr( $values['content_block_add_type_plugin'], 0, 8 ) === 'db_feed_' )
				{
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=blocks&do=form&subnode=1&block_type=plugin&block_plugin=' . $values['content_block_add_type_plugin'] . '&block_plugin_app=dcudash&parent=' . \IPS\Request::i()->parent ) );
				}
				else
				{
					list( $type, $value, $key ) = explode( '__', $values['content_block_add_type_plugin'] );
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=blocks&do=form&subnode=1&block_type=plugin&block_plugin=' . $key . "&block_plugin_{$type}={$value}" . '&parent=' . \IPS\Request::i()->parent ) );
				}
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=blocks&do=form&subnode=1&block_type=' . $values['content_block_add_type'] . '&block_editor=' . $values['content_block_add_custom_type'] . '&parent=' . \IPS\Request::i()->parent ) );
			}
		}
		
		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( \IPS\Member::loggedIn()->language()->addToStack('content_block_block_add'), $form, FALSE );
		\IPS\Output::i()->title   = \IPS\Member::loggedIn()->language()->addToStack('content_block_block_add');
	}

    /**
     * View external embed options
     *
     * @return	void
     */
    public function embedOptions()
    {
        $block = \IPS\dcudash\Blocks\Block::load( \IPS\Request::i()->id );
        $embedKey = md5( $block->key . time() );
        /* Output */
        \IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('block_embed_title');
        \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( '', \IPS\Theme::i()->getTemplate( 'blocks', 'dcudash', 'admin' )->embedCode( $block, $embedKey ) );;
    }
}