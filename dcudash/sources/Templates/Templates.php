<?php
/**
 * @brief		Templates Model
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Template Model
 */
class _Templates extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'template_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Table
	 */
	public static $databaseTable = 'dcudash_templates';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'template_key', 'template_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();
		
	/**
	 * @brief	Retusn all types
	 */
	const RETURN_ALL = 1;
	
	/**
	 * @brief	Returns block templates
	 */
	const RETURN_BLOCK = 2;
	
	/**
	 * @brief	Return dash templates
	 */
	const RETURN_PAGE = 4;
	
	/**
	 * @brief	Return database templates
	 */
	const RETURN_DATABASE = 8;

	/**
	 * @brief	Return just css type
	 */
	const RETURN_ONLY_CSS = 16;

	/**
	 * @brief	Return just js type
	 */
	const RETURN_ONLY_JS = 32;

	/**
	 * @brief	Return just template type
	 */
	const RETURN_ONLY_TEMPLATE = 64;

	/**
	 * @brief	Return just contents of dcudash_templates ignoring IN_DEV and DESIGNERS' MODE
	 */
	const RETURN_DATABASE_ONLY = 128;

	/**
	 * @brief	Return both IN_DEV and database templates
	 */
	const RETURN_DATABASE_AND_IN_DEV = 256;
	
	/**
	 * @brief	Default database template group names
	 */
	public static $databaseDefaults = array(
		'featured'   => 'category_dashboards',
		'form'	     => 'form',
		'display'    => 'display',
		'listing'    => 'listing',
		'categories' => 'category_index'
	);
	
	/**
	 * Ensure that the template is calling the correct groups
	 *
	 * @param	string	$group	Group to load templates from
	 * @return  void
	 */
	public static function fixTemplateTags( $group )
	{
		$templates = iterator_to_array( \IPS\Db::i()->select( '*', 'dcudash_templates', array( array( 'template_group=?', $group ) ) )->setKeyField('template_title') );
		
		foreach( $templates as $template )
		{
			$save = array();
			
			/* Make sure template tags call the correct group */
			if ( mb_stristr( $template['template_content'], '{template' ) )
			{
				preg_match_all( '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/', $template['template_content'], $matches, PREG_SET_ORDER );

				/* Work out the plugin and the values to pass */
				foreach( $matches as $index => $array )
				{
					preg_match_all( '/(.+?)=' . $array[ 2 ] . '(.+?)' . $array[ 2 ] . '\s?/', $array[ 1 ], $submatches );

					$plugin = array_shift( $submatches[ 1 ] );
					if ( $plugin == 'template' )
					{
						$value   = array_shift( $submatches[ 2 ] );
						$options = array();

						foreach ( $submatches[ 1 ] as $k => $v )
						{
							$options[ $v ] = $submatches[ 2 ][ $k ];
						}

						if ( isset( $options['app'] ) and $options['app'] == 'dcudash' and isset( $options['location'] ) and $options['location'] == 'database' and isset( $options['group'] ) and $options['group'] != $template['template_original_group'] )
						{
							if ( in_array( $value, array_keys( $templates ) ) )
							{
								$options['group'] = $group;

								$replace = '{template="' . $value . '" app="' . $options['app'] . '" location="' . $options['location'] . '" group="' . $options['group'] . '" params="' . ( isset($options['params']) ? $options['params'] : NULL ) . '"}';
								$save['template_content'] = str_replace( $matches[$index][0], $replace, $template['template_content'] );
							}
						}
						
						if ( count( $save ) )
						{
							\IPS\Db::i()->update( 'dcudash_templates', $save, array( 'template_id=?', $template['template_id'] ) );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Load Record
	 * Overloaded so we can force loading by key by default but still retain the template_id field as the primary key so
	 * save still updates the primary ID.
	 *
	 * @see		\IPS\Db::build
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function load( $id, $idField=NULL, $extraWhereClause=NULL )
	{
		if ( ! is_numeric( $id ) and $idField === NULL )
		{
			$idField = 'template_key';
		}
		
		if ( ! is_numeric( $id ) and ( \IPS\IN_DEV or \IPS\Theme::designersModeEnabled() ) )
		{
			$templates = \IPS\dcudash\Theme::i()->getRawTemplates( NULL, NULL, NULL, \IPS\dcudash\Theme::RETURN_AS_OBJECT );

			if ( isset( $templates[ $id ] ) )
			{
				return $templates[ $id ];
			}
		}
			
		try
		{
			return parent::load( $id, $idField, $extraWhereClause );
		}
		catch( \OutOfRangeException $ex )
		{
			throw $ex;
		}
	}
	
	/**
	 * Make a group_name readable (Group Name)
	 *
	 * @param	string	$name		Group name from the database
	 * @return	string
	 */
	public static function readableGroupName( $name )
	{
		if ( $name === 'js' )
		{
			return 'JS';
		}
		else if ( $name === 'css' )
		{
			return 'CSS';
		}

		return ucwords( str_replace( array( '-', '_' ), ' ', $name ) );
	}
	
	/**
	 * Get all template group
	 *
	 * @param	int|constant	$returnType		Determines the content returned
	 * @return	array
	 */
	public static function getGroups( $returnType=1 )
	{
		$where  = array();
		$return = array();
		$locations = NULL;
		
		if ( is_string( $returnType ) )
		{
			switch( $returnType )
			{
				case 'all':
					$returnType = self::RETURN_ALL;
				break;
				case 'block':
					$returnType = self::RETURN_BLOCK;
				break;
				case 'dash':
					$returnType = self::RETURN_PAGE;
				break;
				case 'database':
					$returnType = self::RETURN_DATABASE;
				break;
			}
		}
		
		if ( $returnType & self::RETURN_ALL )
		{
			$where[] = array( 'template_location !=?', NULL );
		}
		else
		{
			$locations = array();
			
			if ( $returnType & self::RETURN_BLOCK )
			{
				$locations[] = 'block';
			}
			
			if ( $returnType & self::RETURN_PAGE )
			{
				$locations[] = 'dash';
			}
			
			if ( $returnType & self::RETURN_DATABASE )
			{
				$locations[] = 'database';
			}
			
			if ( ! count( $locations ) )
			{
				throw new \UnexpectedValueException();
			}
			
			$where[] = array( "template_location IN ('" . implode( "','", $locations ) . "')" );
		}
		
		foreach( \IPS\Db::i()->select( 'template_group', static::$databaseTable, $where, 'template_group ASC', NULL, 'template_group' ) as $template )
		{
			$return[ $template ] = $template;
		}
		
		return $return;
	}
	
	/**
	 * Get all templates
	 *
	 * @param	int|constant	$returnType		Determines the content returned
	 * @return	array
	 */
	public static function getTemplates( $returnType=1 )
	{
		$where  = array();
		$return = array();
		$locations = NULL;

		if ( ( \IPS\IN_DEV or \IPS\Theme::designersModeEnabled() ) AND ( $returnType & self::RETURN_DATABASE_AND_IN_DEV ) AND ! ( $returnType & self::RETURN_DATABASE_ONLY ) )
		{
			$flags = \IPS\dcudash\Theme::RETURN_AS_OBJECT;

			if ( $returnType & self::RETURN_ONLY_TEMPLATE )
			{
				$flags += \IPS\dcudash\Theme::RETURN_ONLY_TEMPLATE;
			}
			else if ( $returnType & self::RETURN_ONLY_CSS )
			{
				$flags += \IPS\dcudash\Theme::RETURN_ONLY_CSS;
			}
			else if ( $returnType & self::RETURN_ONLY_JS )
			{
				$flags += \IPS\dcudash\Theme::RETURN_ONLY_JS;
			}
			else
			{
				if ( $returnType & self::RETURN_BLOCK )
				{
					$flags += \IPS\dcudash\Theme::RETURN_BLOCK;
				}

				if ( $returnType & self::RETURN_PAGE )
				{
					$flags += \IPS\dcudash\Theme::RETURN_PAGE;
				}

				if ( $returnType & self::RETURN_DATABASE )
				{
					$flags += \IPS\dcudash\Theme::RETURN_DATABASE;
				}
			}

			$return = \IPS\dcudash\Theme::i()->getRawTemplates( 'dcudash', NULL, NULL, $flags );
		}

		if ( ! ( \IPS\IN_DEV or \IPS\Theme::designersModeEnabled() ) OR ( $returnType & self::RETURN_DATABASE_AND_IN_DEV ) OR ( $returnType & self::RETURN_DATABASE_ONLY ) )
		{
			if ( $returnType & self::RETURN_ALL )
			{
				$where[] = array( 'template_location !=?', NULL );
			}
			else if ( $returnType & self::RETURN_ONLY_TEMPLATE )
			{
				$where[] = array( 'template_type = ?', 'template' );
			}
			else if ( $returnType & self::RETURN_ONLY_CSS )
			{
				$where[] = array( 'template_type = ?', 'css' );
			}
			else if ( $returnType & self::RETURN_ONLY_JS )
			{
				$where[] = array( 'template_type = ?', 'js' );
			}
			else
			{
				$locations = array();

				if ( $returnType & self::RETURN_BLOCK )
				{
					$locations[] = 'block';
				}

				if ( $returnType & self::RETURN_PAGE )
				{
					$locations[] = 'dash';
				}

				if ( $returnType & self::RETURN_DATABASE )
				{
					$locations[] = 'database';
				}

				if ( !count( $locations ) )
				{
					throw new \UnexpectedValueException();
				}

				$where[] = array( "template_location IN ('" . implode( "','", $locations ) . "')" );
			}

			foreach ( \IPS\Db::i()->select( '*', static::$databaseTable, $where, 'template_user_edited DESC' ) as $template )
			{
				/* user_edited version is returned first, so only add to the array if the key isn't already in $return */
				if ( !isset( $return[ $template['template_key'] ] ) )
				{
					$return[ $template['template_key'] ] = static::constructFromData( $template );
				}
			}
		}

		return $return;
	}
	
	/**
	 * Construct Load Query
	 * Overloaded so we return the user_edited version where available
	 *
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to
	 * @param	mixed		$extraWhereClause	Additional where clause(s)
	 * @return	\IPS\Db\Select
	 */
	protected static function constructLoadQuery( $id, $idField, $extraWhereClause )
	{
		$where = array( array( $idField . '=?', $id ) );
		if( $extraWhereClause !== NULL )
		{
			if ( !is_array( $extraWhereClause ) or !is_array( $extraWhereClause[0] ) )
			{
				$extraWhereClause = array( $extraWhereClause );
			}
			$where = array_merge( $where, $extraWhereClause );
		}
	
		return static::db()->select( '*', static::$databaseTable, $where, 'template_user_edited DESC' );
	}

	/**
	 * Generate a tree of templates
	 *
	 * @param array $templates	Template data from the database
	 * @return array
	 */
	public static function buildTree( $templates )
	{
		$return = array();

		foreach( $templates as $id => $template )
		{
			$return[ $template->location ][ $template->group ][ $template->key ] = $template;
		}

		return $return;
	}
	
	/**
	 * Add a new template
	 * 
	 * @param	array	$template	Template Data
	 * @return	object	\IPS\dcudash\Templates
	 */
	public static function add( $template )
	{
		$newTemplate = new static;

		foreach( $template as $_k => $_v )
		{
			$newTemplate->$_k	= $_v;
		}

		$newTemplate->_new         = TRUE;
		$newTemplate->user_created = 1;
		$newTemplate->user_edited  = 1;
		$newTemplate->master       = 0;

		$newTemplate->save();

		/* Create a unique key */
		$newTemplate->key = $newTemplate->location . '_' . \IPS\Http\Url\Friendly::seoTitle( $newTemplate->title ) . '_' . $newTemplate->id;

		/* Make sure there's no double __ in there */
		foreach( array( 'group', 'title', 'key' ) as $field )
		{
			if ( mb_strstr( $newTemplate->$field, '__' ) )
			{
				$newTemplate->$field = str_replace( '__', '_', $newTemplate->$field );
			}
		}

		$newTemplate->save();
		
		return $newTemplate;
	}
	
	/**
	 * Removes all stored files so they can be rebuilt on the fly
	 *
	 * @return void
	 */
	public static function deleteCompiledFiles()
	{
		foreach( \IPS\Db::i()->select( '*', 'dcudash_templates', array( 'template_file_object IS NOT NULL' ) ) as $template )
		{
			try
			{
				\IPS\File::get( 'core_Theme', $template['template_file_object'] )->delete();
			}
			catch( \Exception $ex ) { }
		}
		
		\IPS\Db::i()->update( 'dcudash_templates', array( 'template_file_object' => NULL ) );
	}
	
	/**
	 * Is suitable to be used for a custom wrapper?
	 *
	 * @return boolean
	 */
	public function isSuitableForCustomWrapper()
	{
		if ( $this->location == 'dash' and preg_match( '#<html([^>]+?)?>#', $this->content ) )
		{
			if ( preg_match( '#\$html(\s|=|,)#', $this->params ) and preg_match( '#\$title(\s|=|,|$)#', $this->params ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Is suitable to be used for a builder column wrapper?
	 *
	 * @return boolean
	 */
	public function isSuitableForBuilderWrapper()
	{
		if ( $this->location == 'dash' and mb_stristr( $this->content, '{template="widgetContainer"' ) )
		{
			return true;
		}

		return false;
	}

	/**
	 * Import templates from an XML file
	 *
	 * @param   string      $file       File to load from (data/ or tmp/)
	 * @param	int|null	$offset     Offset to begin import from
	 * @param	int|null	$limit	    Number of rows to import
	 * @param   boolean     $update   	If updating, files written as master templates
	 * @return	bool		Rows imported (true) or none imported (false)
	 */
	public static function importXml( $file, $offset=NULL, $limit=NULL, $update=TRUE )
	{
		$i		= 0;
		$worked	= false;
	
		if( file_exists( $file ) )
		{
			/* First, delete any existing skin data for this app. */
			if( $offset === NULL OR $offset === 0 )
			{
				if ( $update === TRUE )
				{
					\IPS\Db::i()->delete( 'dcudash_templates', array( 'template_master=1' ) );
					\IPS\dcudash\Theme::deleteCompiledTemplate( 'dcudash' );
				}
			}

			/* Open XML file */
			$xml = new \IPS\Xml\XMLReader;
			$xml->open( $file );
			$xml->read();

			while( $xml->read() )
			{
				if( $xml->nodeType != \XMLReader::ELEMENT )
				{
					continue;
				}

				$i++;

				if ( $offset !== null )
				{
					if ( $i - 1 < $offset )
					{
						$xml->next();
						continue;
					}
				}

				if( $xml->name == 'template' )
				{
					$save = array(
						'template_content'        => $xml->readString(),
						'template_master'         => 1,
					    'template_original_group' => $xml->getAttribute('template_group'),
					    'template_file_object'    => NULL
					);

					foreach( array('key', 'title', 'desc', 'location', 'group', 'params', 'app', 'type' ) as $field )
					{
						$save[ 'template_' . $field ] = $xml->getAttribute( 'template_' . $field );
					}

					\IPS\Db::i()->insert( 'dcudash_templates', $save );
					$worked	= true;
				}

				if( $limit !== null AND $i === ( $limit + $offset ) )
				{
					break;
				}
			}
		}

		return $worked;
	}

	/**
	 * Delete
	 * Overloaded to protect inheritence
	 * 
	 *  @return void
	 */
	public function delete()
	{
		\IPS\dcudash\Theme::deleteCompiledTemplate( 'dcudash', $this->location, $this->group );

		if ( $this->user_created )
		{
			\IPS\Db::i()->delete( 'dcudash_templates', array( 'template_key=? AND template_user_created=?', $this->key, 1 ) );

			if ( isset( static::$multitons[ $this->id ] ) )
			{
				unset( static::$multitons[ $this->id ] );
			}
		}
		else
		{
			if ( $this->user_edited )
			{
				\IPS\Db::i()->delete( 'dcudash_templates', array( 'template_key=? AND template_user_edited=?', $this->key, 1 ) );

				if ( isset( static::$multitons[ $this->id ] ) )
				{
					unset( static::$multitons[ $this->id ] );
				}
			}
			else
			{
				throw new \OutOfRangeException('CANNOT_DELETE');
			}
		}
	}

	/**
	 * Get the inherited string
	 *
	 * @return string
	 */
	public function get__inherited()
	{
		if ( $this->user_created )
		{
			return 'custom';
		}
		elseif ( $this->user_edited )
		{
			return 'changed';
		}

		return 'original';
	}

	/**
	 * Get the file object
	 *
	 * @return string
	 */
	public function get__file_object()
	{
		if ( ! $this->file_object )
		{
			$content = $this->content;
			
			/* Build on demand */
			if ( $this->type != 'js' and ( mb_stristr( $this->content, "{block=" ) or mb_stristr( $this->content, "{{if" ) or mb_stristr( $this->content, "{media=" ) ) )
			{
				$functionName = 'css_' . mt_rand();
				\IPS\Theme::makeProcessFunction( str_replace( '\\', '\\\\', $content ), $functionName );
				$functionName = "IPS\Theme\\{$functionName}";
				$content = $functionName();
			}
			
			$this->file_object = (string) \IPS\File::create( 'dcudash_Dashes', $this->title, $content ?: ' ', 'dash_objects', TRUE );
			parent::save(); # Go to parent save to prevent $this->save() from wiping file objects
		}

		return $this->file_object;
	}

	/**
	 * Save
	 * 
	 * @return void
	 */
	public function save()
	{
		/* Trash file object if appropriate */
		if ( $this->file_object )
		{
			try
			{
				\IPS\File::get( 'dcudash_Dashes', $this->file_object )->delete();
			}
			catch ( \Exception $e )
			{
				/* Just to be sure nothing is throw, we don't care too much if it's not deleted */
			}

			/* Trash all cached dash file objects too */
			\IPS\dcudash\Dashes\Dash::deleteCachedIncludes( $this->file_object );

			$this->file_object = NULL;
		}

		/* Should we copy this to a new template and then save it? */
		if ( ! $this->user_edited )
		{
			/* Infinite loop is only cool as Apple's address */
			$clone = new \IPS\dcudash\Templates;
			
			$clone->_data   = $this->_data;
			$clone->changed = $this->changed;
			
			$clone->id = NULL;
			$clone->user_edited = 1;
			$clone->master = 0;
			$clone->_new = TRUE;
			$clone->save();

			$key = \strtolower( 'template_dcudash_' . \IPS\dcudash\Theme::makeBuiltTemplateLookupHash( 'dcudash', $clone->location, $clone->group ) . '_' . $clone->group );
		}
		else
		{
			$key = \strtolower( 'template_dcudash_' . \IPS\dcudash\Theme::makeBuiltTemplateLookupHash( 'dcudash', $this->location, $this->group ) . '_' . $this->group );

			parent::save();
		}

		/* Clear store */
		unset( \IPS\Data\Store::i()->$key );
	}
}