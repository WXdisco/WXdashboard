<?php
/**
 * @brief		File Storage Extension: Records
 * @package		DCU Dashboard customized for WX Dashboard (WX Disco)
 * @author		Gary Cornell for devCU Software Open Source Projects
 * @copyright		c) 2018 devCU Software
 * @contact		gary@devcu.com
 * @site		https://www.devcu.com
 * @Source		https://github.com/WXdisco/wxdashboard  
 * @since		12 DEC 2018
 * @version		1.0.0 Beta 1
 */

namespace IPS\dcudash\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Remove custom databases
 */
class _Databases
{
    /**
     * Constructor
     *
     *
     */
    public function __construct()
    {
    }

    /**
     * Uninstall custom databases
     *
     * @return void
     */
    public function preUninstall( )
    {
        if ( \IPS\Db::i()->checkForTable( 'dcudash_databases' ) )
        {
            foreach ( \IPS\Db::i()->select( '*', 'dcudash_databases') as $db )
            {
                /* The content router only returns databases linked to dashes. In theory, you may have linked a database and then removed it,
                    so the method to remove all app content from the search index fails, so we need to account for that here: */
                \IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\dcudash\Records' . $db['database_id'] );
            }
        }
    }

    /**
     * Uninstall custom databases
     *
     * @return void
     */
    public function postUninstall()
    {
        /* dcudash_databases has been removed */
        $tables = array();
        try
        {
            $databaseTables = \IPS\Db::i()->query("SHOW TABLES LIKE '" . \IPS\Db::i()->prefix . "dcudash_custom_database_%'" )->fetch_assoc();
            if ( $databaseTables )
            {
                foreach( $databaseTables as $row )
                {
                    if( is_array( $row ) )
                    {
                        $tables[] = array_pop($row);
                    }
                    else
                    {
                        $tables[] = $row;
                    }
                }
            }

        }
        catch( \IPS\Db\Exception $ex ) { }

        foreach( $tables as $table )
        {
            if ( \IPS\Db::i()->checkForTable( $table ) )
            {
                \IPS\Db::i()->dropTable( $table );
            }
        }

        if ( isset( \IPS\Data\Store::i()->dcudash_menu ) )
        {
            unset( \IPS\Data\Store::i()->dcudash_menu );
        }
    }
}