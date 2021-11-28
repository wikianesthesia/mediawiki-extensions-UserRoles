<?php


namespace MediaWiki\Extension\UserRoles\Parser;

use Parser;
use PPFrame;
use MediaWiki\Extension\UserRoles\UserRoles;

class UserRolesList {
    public static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
        global $wgUserRolesDefaultTemplate;

        $content = '';

        $roleName = $args[ 'role' ] ?? null;

        $role = UserRoles::getRole( $roleName );

        if( !$role ) {
            return $content;
        }

        $templateId = $args[ 'template' ] ?? $wgUserRolesDefaultTemplate;
        $template = UserRoles::getTemplate( $templateId );

        $useDisplayName = $args[ 'usedisplayname' ] ?? true;

        $passthruArgs = [
            'minpriority' => $args[ 'minpriority' ] ?? null,
            'usedisplayname' => $args[ 'usedisplayname' ] ?? null
        ];

        $usersContent = '';

        foreach( $role->getUsers( $useDisplayName ) as $user ) {
            $userRolesUserArgs = [
                'user="' . $user->getName() . '"',
                'template="' . $templateId . '"'
            ];

            foreach( $passthruArgs as $passthruArgName => $passthruArgValue ) {
                if( !is_null( $passthruArgValue ) ) {
                    $userRolesUserArgs[] = $passthruArgName . '=' . $passthruArgValue;
                }
            }

            $usersContent .= '<userrolesuser ' . implode( ' ', $userRolesUserArgs ) . ' />';
        }

        $content = '{{' . $template->getListTemplate() . '|users=' . $usersContent . '}}';

        // TODO find a more elegant way to always have an updated list. Is it possible to change the cache expiry for
        // all pages using this tag in a relevant hook (i.e. when a user is added or removed from a usergroup or
        // Mediawiki:Userroles-definition is updated?
        $parser->getOutput()->updateCacheExpiry( 0 );

        return $parser->recursiveTagParse( $content );
    }
}