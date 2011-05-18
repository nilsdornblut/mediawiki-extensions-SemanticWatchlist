<?php

/**
 * Static class for hooks handled by the Semantic Watchlist extension.
 * 
 * @since 0.1
 * 
 * @file SemanticWatchlist.hooks.php
 * @ingroup SemanticWatchlist
 * 
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class SWLHooks {

    /**
     * Handle the onDataChanged hook of SMW >1.6, which gets called
     * every time the value of a propery changes somewhere.
     *
     * @since 0.1
     *
     * @param SMWStore $store
     * @param SMWChangeSet $changes
     * 
     * @return true
     */
	public static function onDataChanged( SMWStore $store, SMWChangeSet $changes ) {
		$changes = new SWLChangeSet( $changes );
		$groups = SWLGroups::getMatchingWatchGroups( $changes->getTitle() );
		
		$wasInserted = $changes->writeToStore( $groups ) != 0;
		
		if ( $wasInserted ) {
	        foreach ( $groups as /* SWLGroup */ $group ) {
        		$group->notifyWatchingUsers( $changes );
    		}			
		}

		return true;
	}

    /**
     * Handles group notification.
     * 
     * @since 0.1
     *
     * @param SWLGroup $group
     * @param array $userIDs
     * @param SMWChangeSet $changes
     *
     * @return true
     */    
    public static function onGroupNotify( SWLGroup $group, array $userIDs, SWLChangeSet $changes ) {
    	foreach ( $userIDs as $userID ) {
    		$user = User::newFromId( $userID );
    		
    		if ( Sanitizer::validateEmail( $user->getEmail() ) ) {
				$lastNotify = $user->getOption( 'swl_last_notify' );
				$lastWatch = $user->getOption( 'swl_last_watch' );
    		
	    		if ( is_null( $lastNotify ) || is_null( $lastWatch ) || $lastNotify < $lastWatch ) {
	    			self::notifyUser( $group, $user, $changes );
	    			$user->setOption( 'swl_last_notify', wfTimestampNow() );
	    			$user->saveSettings();
	    		}    			
    		}
    	}
    	
        return true;
    }
    
    /**
     * Notifies a single user of the changes made to properties in a single edit.
     * 
     * @param SWLGroup $group
     * @param User $user
     * @param SWLChangeSet $changes
     * 
     * @return Status
     */
    protected static function notifyUser( SWLGroup $group, User $user, SWLChangeSet $changes ) {
    	$emailText = '';
    	
    	// TODO
    	
    	return $user->sendMail(
    		wfMsgReal( 'swl-email-propschanged', array(), true, $user->getOption( 'language' ) ),
    		$emailText
    	);
    }

	/**
	 * Schema update to set up the needed database tables.
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return true
	 */
	public static function onSchemaUpdate( /* DatabaseUpdater */ $updater = null ) {
		global $wgDBtype;

		if ( $wgDBtype == 'mysql' ) {
            $updater->addExtensionUpdate( array(
                'addTable',
                'swl_groups',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
            $updater->addExtensionUpdate( array(
                'addTable',
                'swl_changes',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
			$updater->addExtensionUpdate( array(
                'addTable',
                'swl_sets',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
			$updater->addExtensionUpdate( array(
                'addTable',
                'swl_sets_per_group',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
			$updater->addExtensionUpdate( array(
                'addTable',
                'swl_users_per_group',
                dirname( __FILE__ ) . '/SemanticWatchlist.sql',
                true
            ) );
		}

		return true;
	}
	
}