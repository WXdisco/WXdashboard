<?php
/**
 * @brief		[Front] Dash Controller
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\modules\front\dashes;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * dash
 */
class _dash extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		parent::execute();
	}

	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage()
	{
		$this->view();
	}
	
	/**
	 * Display a dash. Sounds simple doesn't it? Well it's not.
	 *
	 * @return	void
	 */
	protected function view()
	{
		$dash = $this->getDash();
		
		/* Database specific checks */
		if ( isset( \IPS\Request::i()->advancedSearchForm ) AND isset( \IPS\Request::i()->d ) )
		{
			/* showTableSearchForm just triggers __call which returns the database dispatcher HTML as we
			 * do not want the dash content around the actual database */
			\IPS\Output::i()->output = $this->showTableSearchForm();
			return;
		}

		if ( \IPS\Request::i()->path == $dash->full_path )
		{
			/* Just viewing this dash, no database categories or records */
			$permissions = $dash->permissions();
			\IPS\Session::i()->setLocation( $dash->url(), explode( ",", $permissions['perm_view'] ), 'loc_dcudash_viewing_dash', array( 'dcudash_dash_' . $dash->_id => TRUE ) );
		}
		
		try
		{
			$dash->output();
		}
		catch ( \ParseError $e )
		{
			\IPS\Log::log( $e, 'dash_error' );
			\IPS\Output::i()->error( 'content_err_dash_500', '2T187/4', 500, 'content_err_dash_500_admin', array(), $e );
		}
	}
	
	/**
	 * Get the current dash
	 * 
	 * @return \IPS\dcudash\Dashes\Dash
	 */
	public function getDash()
	{
		$dash = null;
		if ( isset( \IPS\Request::i()->dash_id ) )
		{
			try
			{
				$dash = \IPS\dcudash\Dashes\Dash::load( \IPS\Request::i()->dash_id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_dash_404', '2T187/1', 404, '' );
			}
		}
		else if ( isset( \IPS\Request::i()->path ) AND  \IPS\Request::i()->path != '/' )
		{
			try
			{
				$dash = \IPS\dcudash\Dashes\Dash::loadFromPath( \IPS\Request::i()->path );
			}
			catch ( \OutOfRangeException $e )
			{
				try
				{
					$dash = \IPS\dcudash\Dashes\Dash::getUrlFromHistory( \IPS\Request::i()->path, ( isset( \IPS\Request::i()->url()->data['query'] ) ? \IPS\Request::i()->url()->data['query'] : NULL ) );

					if( (string) $dash == (string) \IPS\Request::i()->url() )
					{
						\IPS\Output::i()->error( 'content_err_dash_404', '2T187/3', 404, '' );
					}

					\IPS\Output::i()->redirect( $dash, NULL, 301 );
				}
				catch( \OutOfRangeException $e )
				{
					\IPS\Output::i()->error( 'content_err_dash_404', '2T187/2', 404, '' );
				}
			}
		}
		else
		{
            try
            {
                $dash = \IPS\dcudash\Dashes\Dash::getDefaultDash();
            }
            catch ( \OutOfRangeException $e )
            {
                \IPS\Output::i()->error( 'content_err_dash_404', '2T257/1', 404, '' );
            }
		}
		
		if ( $dash === NULL )
		{
            \IPS\Output::i()->error( 'content_err_dash_404', '2T257/2', 404, '' );
		}

		if ( ! $dash->can('view') )
		{
			\IPS\Output::i()->error( 'content_err_dash_403', '2T187/3', 403, '' );
		}
		
		/* Set the current dash, so other blocks, DBs, etc don't have to figure out where they are */
		\IPS\dcudash\Dashes\Dash::$currentDash = $dash;
		
		return $dash;
	}
	
	/**
	 * Capture database specific things
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$dash = $this->getDash();
		$dash->setTheme();
		$databaseId = ( isset( \IPS\Request::i()->d ) ) ? \IPS\Request::i()->d : $dash->getDatabase()->_id;

		if ( $databaseId !== NULL )
		{
			try
			{
				if ( \IPS\Request::i()->isAjax() )
				{
					return \IPS\dcudash\Databases\Dispatcher::i()->setDatabase( $databaseId )->run();
				}
				else
				{
					$dash->output();
				}
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_dash_404', '2T257/3', 404, '' );
			}
		}
	}

	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed()
	{
		return $this->__call( 'embed', func_get_args() );
	}
}