<?php
/**
 * @brief		Database Widget
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
 * Database Widget
 */
class _Database extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Database';
	
	/**
	 * @brief	App
	 */
	public $app = 'dcudash';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * @brief	HTML if widget is called more than once, we store it.
	 */
	protected static $html = NULL;
	
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

 		$databases = array();
	    $disabled  = array();

 		foreach( \IPS\dcudash\Databases::databases() as $db )
 		{
		    $databases[ $db->id ] = $db->_title;

		    if ( $db->dash_id )
		    {
			    $disabled[] = $db->id;

				try
				{
					$dash = \IPS\dcudash\Dashes\Dash::load( $db->dash_id );
					$databases[ $db->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_db_in_use_by_dash', NULL, array( 'sprintf' => array( $db->_title, $dash->full_path ) ) );
				}
				catch( \OutOfRangeException $ex )
				{
					unset( $databases[ $db->id ] );
				}
		    }
 		}

	    if ( ! count( $databases ) )
	    {
		    $form->addMessage('dcudash_err_no_databases_to_use');
	    }
 		else
	    {
			$form->add( new \IPS\Helpers\Form\Select( 'database', ( isset( $this->configuration['database'] ) ? $this->configuration['database'] : NULL ), FALSE, array( 'options' => $databases, 'disabled' => $disabled ) ) );
	    }

		return $form;
 	}

	/**
	 * Pre save
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( $values )
	{
		if ( \IPS\Request::i()->dashID and $values['database'] )
		{
			\IPS\dcudash\Dashes\Dash::load( \IPS\Request::i()->dashID )->mapToDatabase( $values['database'] );
		}

		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( static::$html === NULL )
		{
			if ( isset( $this->configuration['database'] ) )
			{
				try
				{
					$database = \IPS\dcudash\Databases::load( intval( $this->configuration['database'] ) );
					
					if ( ! $database->dash_id and \IPS\dcudash\Dashes\Dash::$currentDash )
					{
						$database->dash_id = \IPS\dcudash\Dashes\Dash::$currentDash->id;
						$database->save();
					}
					
					static::$html = \IPS\dcudash\Databases\Dispatcher::i()->setDatabase( $database->id )->run();
				}
				catch ( \OutOfRangeException $e )
				{
					static::$html = '';
				}
			}
			else
			{
				return '';
			}
		}
		
		return static::$html;
	}
}