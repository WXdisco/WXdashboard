<?php
/**
 * @brief		WYSIWYG Widget
 * @package		DCU Dashboard
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		(c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * WYSIWYG Widget
 */
class _Wysiwyg extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Wysiwyg';
	
	/**
	 * @brief	App
	 */
	public $app = 'dcudash';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Specify widget configuration
	 *
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
 		if ( $form === null )
 		{
	 		$form = new \IPS\Helpers\Form;
 		} 
 		
		$form->add( new \IPS\Helpers\Form\Editor( 'content', ( isset( $this->configuration['content'] ) ? $this->configuration['content'] : NULL ), FALSE, array(
			'app'			=> $this->app,
			'key'			=> 'Widgets',
			'autoSaveKey' 	=> 'widget-' . $this->uniqueKey,
			'attachIds'	 	=> isset( $this->configuration['content'] ) ? array( 0, 0, $this->uniqueKey ) : NULL
		) ) );
		
		return $form;
 	}
 	
 	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete()
	{
		foreach( \IPS\Db::i()->select( '*', 'core_attachments_map', array( array( 'location_key=? and id3=?', 'dcudash_Widgets', $this->uniqueKey ) ) ) as $map )
		{
			try
			{				
				$attachment = \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $map['attachment_id'] ) )->first();
				
				\IPS\Db::i()->delete( 'core_attachments_map', array( array( 'attachment_id=?', $attachment['attach_id'] ) ) );
				\IPS\Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );
				
				
				\IPS\File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
				if ( $attachment['attach_thumb_location'] )
				{
					\IPS\File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
				}
			}
			catch ( \Exception $e ) { }
		}
	}
	
 	/**
 	 * Pre-save config method
 	 *
 	 * @param	array	$values		Form values
 	 * @return void
 	 */
 	public function preConfig( $values=array() )
 	{
	 	\IPS\File::claimAttachments( 'widget-' . $this->uniqueKey, 0, 0, $this->uniqueKey );
	 	
	 	return $values;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_core.js', 'core' ) );
		return $this->output( isset( $this->configuration['content'] ) ? $this->configuration['content'] : '' );
	}
}