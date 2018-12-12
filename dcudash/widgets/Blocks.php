<?php
/**
 * @brief		Custom Blocks Block
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @subpackage		Dashboard Content
 * @since		12 DEC 2018
 * @version		1.0
 */

namespace IPS\dcudash\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom block Widget
 */
class _Blocks extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Blocks';
	
	/**
	 * @brief	App
	 */
	public $app = 'dcudash';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';
	
	/**
	 * Constructor
	 *
	 * @param	String				$uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param	null|string|array	$access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	null|string			$orientation			Orientation (top, bottom, right, left)
	 * @param	boolean				$allowReuse				If true, when the block is used, it will remain in the sidebar so it can be used again.
	 * @param	string				$menuStyle				Menu is a drop down menu, modal is a bigger modal panel.
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		try
		{
			if (  isset( $configuration['dcudash_widget_custom_block'] ) )
			{
				$block = \IPS\dcudash\Blocks\Block::load( $configuration['dcudash_widget_custom_block'], 'block_key' );
				if ( $block->type === 'custom' AND ! $block->cache )
				{
					$this->neverCache = TRUE;
				}
				else if ( $block->type === 'plugin' )
				{
					try
					{
						/* loads and JS and CSS needed */
						$block->orientation = $orientation;
						$block->widget()->init();
					}
					catch( \Exception $e ) { }
				}
			}
		}
		catch( \Exception $e ) { }
		
		parent::__construct( $uniqueKey, $configuration, $access, $orientation );
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param   \IPS\Helpers\Form   $form       Form Object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
	    if ( $form === null )
	    {
		    $form = new \IPS\Helpers\Form;
	    }
		
		/* A block may be deleted on the back end */
		$block = NULL;
		try
		{
			if ( isset( $this->configuration['dcudash_widget_custom_block'] ) )
			{
				$block = \IPS\dcudash\Blocks\Block::load( $this->configuration['dcudash_widget_custom_block'], 'block_key' );
			}
		}
		catch( \OutOfRangeException $e ) { }
		
	    $form->add( new \IPS\Helpers\Form\Node( 'dcudash_widget_custom_block', $block, FALSE, array(
            'class' => '\IPS\dcudash\Blocks\Container',
            'permissionCheck' => function( $node )
                {
	                if ( $node instanceof \IPS\dcudash\Blocks\Container )
	                {
		                return FALSE;
	                }

	                return TRUE;
                }
        ) ) );

	    return $form;
 	}

	/**
	 * Pre config
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( $values )
	{
		$newValues = array();

		if ( isset( $values['dcudash_widget_custom_block'] ) )
		{
			$newValues['dcudash_widget_custom_block'] = $values['dcudash_widget_custom_block']->key;
		}

		return $newValues;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( isset( $this->configuration['dcudash_widget_custom_block'] ) )
		{
			return (string) \IPS\dcudash\Blocks\Block::display( $this->configuration['dcudash_widget_custom_block'], $this->orientation );
		}

		return '';
	}
}