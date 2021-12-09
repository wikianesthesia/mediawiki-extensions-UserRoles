<?php

namespace MediaWiki\Extension\UserRoles;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Title;
use User;

class UserRoles {

    /**
     * @var bool
     */
    protected static $definitionLoaded = false;

    /**
     * @var Role[]
     */
    protected static $roles = [];

    /**
     * @var Template[]
     */
    protected static $templates = [];

    /**
     * @var UserInfo[]
     */
    protected static $userInfo = [];

    /**
     * @var Role[][]
     */
    protected static $userRoles = [];


    /**
     * @param string $name
     * @return false|Role
     */
    public static function getRole( string $name ) {
        static::loadUserRolesDefinition();

        return static::$roles[ Role::getIdFromName( $name ) ] ?? false;
    }

    public static function getRolesForUser( int $userId ): array {
        static::loadUserRolesDefinition();

        if( !isset( static::$userRoles[ $userId ] ) ) {
            $userRoles = [];

            foreach( static::$roles as $role ) {
                if( $role->hasUser( $userId ) ) {
                    $userRoles[] = $role;
                }
            }

            usort( $userRoles, function( Role $a, Role $b ) {
                if( $a->getPriority() === $b->getPriority() ) {
                    return $a->getName() < $b->getName() ? -1 : 1;
                }

                return $a->getPriority() > $b->getPriority() ? -1 : 1;
            } );

            static::$userRoles[ $userId ] = $userRoles;
        }

        return static::$userRoles[ $userId ];
    }

    /**
     * @param string $templateId
     * @return false|Template
     */
    public static function getTemplate( string $templateId ) {
        static::loadUserRolesDefinition();

        return static::$templates[ $templateId ] ?? false;
    }

    /**
     * @param int $userId
     * @return false|UserInfo
     */
    public static function getUserInfo( int $userId ) {
        static::loadUserRolesDefinition();

        if( !isset( static::$userInfo[ $userId ] ) ) {
            static::$userInfo[ $userId ] = new UserInfo( $userId );
        }

        return static::$userInfo[ $userId ];
    }

    /**
     * @param string $userGroup
     * @return User[]
     */
    public static function getUsersForUserGroup( string $userGroup ): array {
        $users = [];

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );

        $res = $dbr->select(
            [ 'user', 'user_groups' ],
            'user_id',
            [
                'ug_group' => $userGroup
            ],
            __METHOD__,
            [],
            [
                'user_groups' => [ 'LEFT JOIN', 'user_id=ug_user' ]
            ]
        );

        foreach( $res as $row ) {
            $user = User::newFromId( $row->user_id );
            if( $user->isRegistered() ) {
                $users[ $user->getId() ] = $user;
            }
        }

        return $users;
    }

    public static function loadUserRolesDefinition() {
        if( !static::$definitionLoaded ) {
            static::$roles = [];
            static::$templates = [];
            static::$userInfo = [];

            $title = Title::makeTitle( NS_MEDIAWIKI, 'Userroles-definition' );

            $revRecord = MediaWikiServices::getInstance()
                ->getRevisionLookup()
                ->getRevisionByTitle( $title );

            if ( !$revRecord
                || !$revRecord->getContent( SlotRecord::MAIN )
                || $revRecord->getContent( SlotRecord::MAIN )->isEmpty()
            ) {
                return false; // don't cache
            }

            $definition = $revRecord->getContent( SlotRecord::MAIN )->getNativeData();

            $definition = preg_replace( '/<!--.*?-->/s', '', $definition );

            $sections = [ 'roles', 'templates', 'userinfo' ];

            foreach( $sections as $section ) {
                $m = [];
                if( preg_match( '/==+\s*(?:' . $section . ')\s*==+\s*(.*?)(?:==|$)/is', $definition, $m ) ) {
                    $lines = preg_split( '/(\r\n|\r|\n)+/', trim( $m[ 1 ] ) );

                    foreach ( $lines as $line ) {
                        if( $section === 'roles' ) {
                            $role = new Role( $line );

                            if( $role ) {
                                static::$roles[ $role->getId() ] = $role;
                            }
                        } elseif( $section === 'templates' ) {
                            $template = new Template( $line );

                            if( $template ) {
                                static::$templates[ $template->getId() ] = $template;
                            }
                        } elseif( $section === 'userinfo' ) {
                            $userInfo = new UserInfo( $line );

                            if( $userInfo ) {
                                static::$userInfo[ $userInfo->getId() ] = $userInfo;
                            }
                        }
                    }
                }
            }
        }

        static::$definitionLoaded = true;
    }
}