<?php

namespace MediaWiki\Extension\UserRoles\Hook;

use MediaWiki\Extension\UserRoles\UserRoles;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use Parser;

class HookHandler implements
    BeforePageDisplayHook,
    ParserFirstCallInitHook {
    public function onBeforePageDisplay( $out, $skin ): void {
        UserRoles::loadUserRolesDefinition();
    }

    /**
     * @param Parser $parser Parser object being initialised
     * @return bool|void True or no return value to continue or false to abort
     */
    public function onParserFirstCallInit( $parser ) {
        $parser->setHook( 'userroleslist', 'MediaWiki\\Extension\\UserRoles\\Parser\\UserRolesList::render' );
        $parser->setHook( 'userrolesuser', 'MediaWiki\\Extension\\UserRoles\\Parser\\UserRolesUser::render' );
        $parser->setHook( 'userrolesroles', 'MediaWiki\\Extension\\UserRoles\\Parser\\UserRolesRoles::render' );
    }
}