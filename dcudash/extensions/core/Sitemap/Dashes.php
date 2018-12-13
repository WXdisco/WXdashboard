<?php
/**
 * @brief		Support Dashes in sitemaps
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Dashes in sitemaps
 */
class _Dashes
{
	/**
	 * @brief	Recommended Settings
	 */
	public $recommendedSettings = array(
		'sitemap_dashes_count'	 => -1,
		'sitemap_dashes_priority' => 1
	);
	
	/**
	 * Settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array(
			'sitemap_dashes_count'	 => new \IPS\Helpers\Form\Number( 'sitemap_dashes_count', \IPS\Settings::i()->sitemap_dashes_count, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, 'sitemap_dashes_count' ),
			'sitemap_dashes_priority' => new \IPS\Helpers\Form\Select( 'sitemap_dashes_priority', \IPS\Settings::i()->sitemap_dashes_priority, FALSE, array( 'options' => \IPS\Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, 'sitemap_dashes_priority' )
		);
	}

	/**
	 * Save settings for ACP configuration
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	public function saveSettings( $values )
	{
		if ( $values['sitemap_configuration_info'] )
		{
			\IPS\Settings::i()->changeValues( array( 'sitemap_dashes_count' => $this->recommendedSettings['sitemap_dashes_count'], 'sitemap_dashes_priority' => $this->recommendedSettings['sitemap_dashes_priority'] ) );
		}
		else
		{
			\IPS\Settings::i()->changeValues( array( 'sitemap_dashes_count' => $values['sitemap_dashes_count'], 'sitemap_dashes_priority' => $values['sitemap_dashes_priority'] ) );
		}
	}
	
	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames()
	{
		$files  = array();
		$class  = '\IPS\dcudash\Dashes\Dash';
		$count  = 0;
		$member = new \IPS\Member;
		$permissionCheck = 'view';
		
		$where = array( array( '(' . \IPS\Db::i()->findInSet( 'perm_' . $class::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . $class::$permissionMap[ $permissionCheck ] . '=? )', '*' ) );
			
		$count = \IPS\Db::i()->select( '*', $class::$databaseTable )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnId, $class::$permApp, $class::$permType ) )
				->count();
				
		$count = ceil( max( $count, \IPS\Settings::i()->sitemap_dashes_count ) / \IPS\Sitemap::MAX_PER_FILE );
		
		for( $i=1; $i <= $count; $i++ )
		{
			$files[] = 'sitemap_dashes_' . $i;
		}

		return $files;
	}

	/**
	 * Generate the sitemap
	 *
	 * @param	string			$filename	The sitemap file to build (should be one returned from getFilenames())
	 * @param	\IPS\Sitemap	$sitemap	Sitemap object reference
	 * @return	void
	 */
	public function generateSitemap( $filename, $sitemap )
	{
		/* We have elected to not add databases to the sitemap */
		if ( ! \IPS\Settings::i()->sitemap_dashes_count )
		{
			return NULL;
		}
		
		$class  = '\IPS\dcudash\Dashes\Dash';
		$count  = 0;
		$member = new \IPS\Member;
		$permissionCheck = 'view';
		$entries = array();
		
		$exploded = explode( '_', $filename );
		$block = (int) array_pop( $exploded );
			
		$offset = ( $block - 1 ) * \IPS\Sitemap::MAX_PER_FILE;
		$limit = \IPS\Sitemap::MAX_PER_FILE;
		
		$totalLimit = \IPS\Settings::i()->sitemap_dashes_count;
		if ( $totalLimit > -1 and ( $offset + $limit ) > $totalLimit )
		{
			if ( $totalLimit < $limit )
			{
				$limit = $totalLimit;
			}
			else
			{
				$limit = $totalLimit - $offset;
			}
		}
			
		$where = array( array( '(' . \IPS\Db::i()->findInSet( 'perm_' . $class::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . $class::$permissionMap[ $permissionCheck ] . '=? )', '*' ) );
			
		$select = \IPS\Db::i()->select( '*', $class::$databaseTable, $where, 'dash_id ASC', array( $offset, $limit ) )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnId, $class::$permApp, $class::$permType ) );

		foreach( $select as $row )
		{
			$item = $class::constructFromData( $row );
			
			$data = array( 'url' => $item->url() );				
			$priority = intval( \IPS\Settings::i()->sitemap_dashes_priority );
			if ( $priority !== -1 )
			{
				$data['priority'] = $priority;
				$entries[] = $data;
			}
		}

		$sitemap->buildSitemapFile( $filename, $entries );
	}

}