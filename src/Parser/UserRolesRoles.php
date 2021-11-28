<?php

namespace MediaWiki\Extension\UserRoles\Parser;

use Parser;
use PPFrame;
use User;
use MediaWiki\Extension\UserRoles\UserRoles;

class UserRolesRoles {
    public static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
        $content = '';

        $userName = $args[ 'user' ] ?? null;

        // Is this a security risk?
        // $delimiter = $args[ 'delimiter' ] ?? ', ';
        $delimiter = ', ';

        $user = User::newFromName( $userName );

        if( !$user || !$user->isRegistered() ) {
            return $content;
        }

        $minPriority = $args[ 'minpriority' ] ?? 0;

        $roles = UserRoles::getRolesForUser( $user->getId() );

        foreach( $roles as $role ) {
            if( $role->getPriority() < $minPriority ) {
                continue;
            }

            if( $content ) {
                $content .= $delimiter;
            }

            $content .= $role->getName();
        }

        // TODO find a more elegant way to always have an updated list. Is it possible to change the cache expiry for
        // all pages using this tag in a relevant hook (i.e. when a user is added or removed from a usergroup or
        // Mediawiki:Userroles-definition is updated?
        $parser->getOutput()->updateCacheExpiry( 0 );

        return $content;
    }
}
