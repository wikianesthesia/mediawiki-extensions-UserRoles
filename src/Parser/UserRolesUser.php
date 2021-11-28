<?php

namespace MediaWiki\Extension\UserRoles\Parser;

use Parser;
use PPFrame;
use User;
use MediaWiki\Extension\UserRoles\UserRoles;

class UserRolesUser {
    public static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
        global $wgUserRolesDefaultTemplate, $wgUserRolesUseRealName;

        $content = '';

        $userName = $args[ 'user' ] ?? null;

        $user = User::newFromName( $userName );

        if( !$user || !$user->isRegistered() ) {
            return $content;
        }

        $templateId = $args[ 'template' ] ?? $wgUserRolesDefaultTemplate;
        $template = UserRoles::getTemplate( $templateId );

        $useDisplayName = $args[ 'usedisplayname' ] ?? true;

        $passthruArgs = [
            'minpriority' => $args[ 'minpriority' ] ?? null
        ];

        $userInfo = UserRoles::getUserInfo( $user->getId() );

        $templateParams = 'user_name=' . $user->getName();

        if( $useDisplayName && $userInfo ) {
            $displayName = $userInfo->getDisplayName();
        } elseif( $wgUserRolesUseRealName ) {
            $displayName = $user->getRealName();
        } else {
            $displayName = $user->getName();
        }

        $templateParams .= '|display_name=' . $displayName;

        if( $userInfo && $userInfo->getImageFile() ) {
            $templateParams .= '|image_file=' . $userInfo->getImageFile();
        }

        $userRolesRolesArgs = [
            'user="' . $user->getName() . '"'
        ];

        foreach( $passthruArgs as $passthruArgName => $passthruArgValue ) {
            if( !is_null( $passthruArgValue ) ) {
                $userRolesRolesArgs[] = $passthruArgName . '=' . $passthruArgValue;
            }
        }

        $templateParams .= '|roles=<userrolesroles ' . implode( ' ', $userRolesRolesArgs ) . ' />';

        $content .= '{{' . $template->getUserTemplate() . '|' . $templateParams . '}}';

        // TODO find a more elegant way to always have an updated list. Is it possible to change the cache expiry for
        // all pages using this tag in a relevant hook (i.e. when a user is added or removed from a usergroup or
        // Mediawiki:Userroles-definition is updated?
        $parser->getOutput()->updateCacheExpiry( 0 );

        return $parser->recursiveTagParse( $content );
    }
}