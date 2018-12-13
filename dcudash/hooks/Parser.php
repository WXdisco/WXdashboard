//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class dcudash_hook_Parser extends _HOOK_CLASS_
{
	/**
	 * Get URL bases (whout schema) that we'll allow iframes from
	 *
	 * @return	array
	 */
	protected static function allowedIFrameBases()
	{
		$return = parent::allowedIFrameBases();
		
		/* If the dashboard root URL is not inside the IPS4 directory, then embeds will fails as the src will not be allowed */
		if ( \IPS\Settings::i()->dcudash_root_dash_url )
		{
			$dashes = iterator_to_array( \IPS\Db::i()->select( 'database_dash_id', 'dcudash_databases', array( 'database_dash_id > 0' ) ) );

			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'dcudash_dashes', array( \IPS\Db::i()->in( 'dash_id', $dashes ) ) ), 'IPS\dcudash\Dashes\Dash' ) as $dash )
			{
				$return[] = str_replace( array( 'http://', 'https://' ), '', $dash->url() );
			}
		}

		return $return;
	}
}
