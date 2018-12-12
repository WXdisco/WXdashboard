<?php
/**
 * @brief		Rss Widget
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
 * Rss Widget
 */
class _Rss extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Rss';
	
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
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
 		if ( $form === null )
		{
	 		$form = new \IPS\Helpers\Form;
 		}

		$form->add( new \IPS\Helpers\Form\Text( 'block_rss_import_title', ( isset( $this->configuration['block_rss_import_title'] ) ? $this->configuration['block_rss_import_title'] : NULL ), TRUE ) );
		$form->add( new \IPS\Helpers\Form\Url( 'block_rss_import_url', ( isset( $this->configuration['block_rss_import_url'] ) ? $this->configuration['block_rss_import_url'] : NULL ), TRUE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'block_rss_import_number', ( isset( $this->configuration['block_rss_import_number'] ) ? $this->configuration['block_rss_import_number'] : 5 ), TRUE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'block_rss_import_cache', ( isset( $this->configuration['block_rss_import_cache'] ) ? $this->configuration['block_rss_import_cache'] : 30 ), TRUE, array(), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('block_rss_import_cache_suffix') ) );

		return $form;
	}
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( $values )
 	{
 		$values['block_rss_import_url'] = (string) $values['block_rss_import_url'];

	    return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( ! isset( $this->configuration['block_rss_import_url'] ) )
		{
			return '';
		}

		$key = "dcudash_rss_import_" . md5( json_encode( $this->configuration ) );

		if ( isset( \IPS\Data\Store::i()->$key ) )
		{
			$cache = \IPS\Data\Store::i()->$key;

			if ( isset( $cache['time'] ) and isset( $cache['items'] ) and $cache['time'] > ( time() - ( $this->configuration['block_rss_import_cache'] * 60 ) ) )
			{
				return $this->output( $cache['items'], $this->configuration['block_rss_import_title'] );
			}
		}

		/* Still here? Best grab the data then */
		try
		{
			$request = \IPS\Http\Url::external( $this->configuration['block_rss_import_url'] )->request()->get();

			$i = 0;
			$items = array();
			if( $request )
			{
				foreach ( $request->decodeXml()->dashboards() as $guid => $dashboard )
				{
					if ( isset( $dashboard['title'] ) and isset( $dashboard['link'] ) )
					{
						$items[ $guid ] = array(
							'title'   => $dashboard['title'],
							'content' => \IPS\Text\Parser::parseStatic( $dashboard['content'], TRUE, NULL, new \IPS\Member ),
							'link'    => (string) $dashboard['link'],
							'date'    => ( $dashboard['date'] instanceof \IPS\DateTime ) ? $dashboard['date']->getTimestamp() : $dashboard['date']
						);
					}

					$i++;

					if ( $i >= $this->configuration['block_rss_import_number'] )
					{
						break;
					}
				}
			}
		}
		catch( \Exception $e )
		{
			$items = array();
		}

		\IPS\Data\Store::i()->$key = array( 'time' => time(), 'items' => $items );

		return $this->output( $items, $this->configuration['block_rss_import_title'] );
	}
}