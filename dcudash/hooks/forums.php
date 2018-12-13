 //<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class dcudash_hook_forums extends _HOOK_CLASS_
{


	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		/* Load forum and verify that it is not used for comments */
		$nodeClass = $this->nodeClass;
		if ( \IPS\Request::i()->subnode )
		{
			$nodeClass = $nodeClass::$subnodeClass;
		}

		try
		{
			$node = call_user_func( "{$nodeClass}::load", \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2S101/J', 404, '' );
		}

		/* Is any database synced with this forum? */
		if ( $db = $node->isUsedByDcudash() )
		{
			\IPS\Member::loggedIn()->language()->words['dcudash_forum_used'] = sprintf( \IPS\Member::loggedIn()->language()->get('dcudash_forum_used'), $db->recordWord( 1 ) );

			\IPS\Output::i()->error( 'dcudash_forum_used', '1T371/1', 403, '' );
		}

		return call_user_func_array( 'parent::delete', func_get_args() );
	}

}
