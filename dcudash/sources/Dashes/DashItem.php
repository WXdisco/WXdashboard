<?php
/**
 * @brief		Dashes Dash Item Model
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\Dashes;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package Item Model
 */
class _DashItem extends \IPS\Content\Item implements \IPS\Content\Searchable
{
	/**
	 * @brief	Application
	 */
	public static $application = 'dcudash';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'dashes';
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'dcudash_dashes';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'dash_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
			
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(

	);
	
	/**
	 * @brief	Title
	 */
	public static $title = 'dcudash_dash';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'files-o';
	
	/**
	 * @brief	Include In Sitemap
	 */
	public static $includeInSitemap = FALSE;
	
	/**
	 * @brief	Can this content be moderated normally from the front-end (will be FALSE for things like Dashes and Commerce Products)
	 */
	public static $canBeModeratedFromFrontend = FALSE;
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns()
	{
		return array( 'dash_id', 'dash_folder_id', 'dash_full_path', 'dash_default' );
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
		if ( ( \IPS\Application::load('dcudash')->default OR \IPS\Settings::i()->dcudash_use_different_gateway ) AND $itemData['dash_default'] AND !$itemData['dash_folder_id'] )
		{
			/* Are we using the gateway file? */
			if ( \IPS\Settings::i()->dcudash_use_different_gateway )
			{
				/* Yes, work out the proper URL. */
				return \IPS\Http\Url::createFromString( \IPS\Settings::i()->dcudash_root_dash_url, TRUE );
			}
			else
			{
				/* No - that's easy */
				return \IPS\Http\Url::internal( '', 'front' );
			}
		}
		else
		{
			return \IPS\Http\Url::internal( 'app=dcudash&module=dashes&controller=dash&path=' . $itemData['dash_full_path'], 'front', 'content_dash_path', array( $itemData['dash_full_path'] ) );
		}
	}
	
	/**
	 * Get HTML for search result display
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$authorData		Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	array		$reputationData	Array of people who have given reputation and the reputation they gave
	 * @param	int|NULL	$reviewRating	If this is a review, the rating
	 * @param	bool		$iPostedIn		If the user has posted in the item
	 * @param	string		$view			'expanded' or 'condensed'
	 * @param	bool		$asItem	Displaying results as items?
	 * @param	bool		$canIgnoreComments	Can ignore comments in the result stream? Activity stream can, but search results cannot.
	 * @param	array		$template	Optional custom template
	 * @param	array		$reactions	Reaction Data
	 * @return	string
	 */
	public static function searchResult( array $indexData, array $authorData, array $itemData, array $containerData = NULL, array $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments=FALSE, $template=NULL, $reactions=array() )
	{
		$indexData['index_title'] = \IPS\Member::loggedIn()->language()->addToStack( 'dcudash_dash_' . $indexData['index_item_id'] );
		return parent::searchResult( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments, $template, $reactions );
	}
		
	/**
	 * Title for search index
	 *
	 * @return	string
	 */
	public function searchIndexTitle()
	{
		$titles = array();
		foreach ( \IPS\Lang::languages() as $lang )
		{
			$titles[] = $lang->get("dcudash_dash_{$this->id}");
		}
		return implode( ' ', $titles );
	}
	
	/**
	 * Content for search index
	 *
	 * @return	string
	 */
	public function searchIndexContent()
	{
		if ( $this->type == 'builder' )
		{
			$content = array();
			foreach( \IPS\Db::i()->select( '*', 'dcudash_dash_widget_areas', array( 'area_dash_id=?', $this->id ) ) as $widgetArea )
			{
				foreach ( json_decode( $widgetArea['area_widgets'], TRUE ) as $widget )
				{
					if ( $widget['app'] == 'dcudash' and $widget['key'] == 'Wysiwyg' )
					{
						$content[] = trim( $widget['configuration']['content'] );
					}
				}
			}
			return implode( ' ', $content );
		}
		else
		{
			return $this->content;
		}
	}
	
	/**
	 * Search Index Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions()
	{
		try
		{
			return \IPS\Db::i()->select( 'perm_view', 'core_permission_index', array( "app='dcudash' AND perm_type='dashes' AND perm_type_id=?", $this->id ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			return '';
		}
	}
}