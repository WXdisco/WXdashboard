<?php
/**
 * @brief		Spotify input class for Form Builder
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\Fields;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Spotify input class for Form Builder
 */
class _Spotify extends \IPS\Helpers\Form\Text
{
	/**
	 * @brief	Default Options
	 */
	public $childDefaultOptions = array(
		'parameters' => array()
	);
	
	/**
	 * Constructor
	 * Sets that the field is required if there is a minimum length and vice-versa
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		$this->childDefaultOptions['placeholder'] = \IPS\Member::loggedIn()->language()->addToStack('field_placeholder_spotify');
		
		/* Call parent constructor */
		call_user_func_array( 'parent::__construct', func_get_args() );
		
		$this->formType = 'text';
	}
	
	/**
	 * Get the display value
	 * 
	 * @param	mixed			$value			Stored value from form
	 * @param	\IPS\dcudash\Field	$customField	Custom Field Object
	 * @return	string
	 */
	public static function displayValue( $value, $customField )
	{
		if( !$value )
		{
			return '';
		}

		$params = $customField->extra;
		
		if ( ! isset( $params['width'] ) )
		{
			$params['width'] = 300;
		}
		
		if ( ! isset( $params['height'] ) )
		{
			$params['height'] = 80;
		}
		
		return \IPS\Theme::i()->getTemplate( 'records', 'dcudash', 'global' )->spotify( $value, $params );
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\DomainException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();
						
		if ( $this->value )
		{
			/* Check the URL is valid */
			if ( !( $this->value instanceof \IPS\Http\Url ) )
			{
				throw new \InvalidArgumentException('form_url_bad');
			}
			
			/* Check its a valid Spotify URL */
			if ( ! mb_stristr( $this->value->data['host'], 'spotify.com' ) )
			{
				throw new \InvalidArgumentException('form_url_bad');
			}
		}
	}
	
	/**
	 * Get Value
	 *
	 * @return	string
	 */
	public function getValue()
	{
		$val = parent::getValue();
		if ( $val and !mb_strpos( $val, '://' ) )
		{
			$val = "http://{$val}";
		}
		
		return $val;
	}
	
	/**
	 * Format Value
	 *
	 * @return	\IPS\Http\Url|string
	 */
	public function formatValue()
	{
		if ( $this->value and !( $this->value instanceof \IPS\Http\Url ) )
		{
			try
			{
				return new \IPS\Http\Url( $this->value );
			}
			catch ( \InvalidArgumentException $e )
			{
				return $this->value;
			}
		}
		
		return $this->value;
	}
}