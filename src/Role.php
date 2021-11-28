<?php

namespace MediaWiki\Extension\UserRoles;

use User;

class Role {

    /**
     * @var string
     */
    protected $excludedByRoleIds = [];

    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var int
     */
    protected $priority = 0;

    /**
     * @var string[]
     */
    protected $roleIds = [];

    /**
     * @var string[]
     */
    protected $userGroups = [];

    /**
     * @var string[]
     */
    protected $userNames = [];

    /**
     * @var User[]
     */
    protected $users;


    public function __construct( string $definition = '' ) {
        // Much of this code is adapted from MediaWikiGadgetsDefinitionRepo::newFromDefinition()
        $m = [];
        if ( !preg_match(
            '/^\*+ *([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)\s*((?:\|[^|]*)+)\s*$/',
            $definition,
            $m
        ) ) {
            return false;
        }

        $this->name = trim( $m[ 1 ] );
        $this->id = static::getIdFromName( $this->name );

        $options = trim( $m[ 2 ] );

        foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
            $arr = preg_split( '/\s*=\s*/', $option, 2 );
            $option = $arr[ 0 ];

            if ( isset( $arr[ 1 ] ) ) {
                $value = $arr[ 1 ];

                switch( $option ) {
                    case 'excludedbyroles':
                        $this->excludedByRoleIds = array_map( static::class . '::getIdFromName', static::explodeList( $value ) );
                        break;
                    case 'priority':
                        $this->priority = $value;
                        break;
                    case 'roles':
                        $this->roleIds = static::explodeList( $value );
                        break;
                    case 'usergroups':
                        $this->userGroups = static::explodeList( $value );
                        break;
                    case 'users':
                        $this->userNames = static::explodeList( $value );
                        break;
                }
            }
        }
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPriority(): int {
        return $this->priority;
    }

    public function getExcludedUsers(): array {
        $excludedUsers = [];

        if( count( $this->excludedByRoleIds ) ) {
            foreach( $this->excludedByRoleIds as $excludedByRoleId ) {
                $role = UserRoles::getRole( $excludedByRoleId );

                if( $role ) {
                    $excludedUsers += $role->getUsers();
                }
            }
        }

        return $excludedUsers;
    }


    /**
     * @return User[]
     */
    public function getUsers(): array {
        $this->loadUsers();

        return $this->users;
    }

    public function hasUser( int $userId ): bool {
        $this->loadUsers();

        return array_key_exists( $userId, $this->users );
    }

    protected function loadUsers( bool $useDisplayName = true ) {
        if( is_null( $this->users ) ) {
            // Users for a role can be defined by other roles, usergroups, or an explicit list of users.
            // The final list of users should be sorted by role priority, role name, and the users display name.

            $roleUsers = [
                0 => [
                    'role' => null,
                    'users' => []
                ]
            ];

            if( count( $this->roleIds ) ) {
                foreach( $this->roleIds as $roleId ) {
                    $role = UserRoles::getRole( $roleId );

                    if( $role ) {
                        $roleUsers[ $roleId ] = [
                            'role' => $role,
                            'users' => $role->getUsers()
                        ];
                    }
                }
            }

            if( count( $this->userGroups ) ) {
                foreach( $this->userGroups as $userGroup ) {
                    $roleUsers[ 0 ][ 'users' ] += UserRoles::getUsersForUserGroup( $userGroup );
                }
            }

            if( count( $this->userNames ) ) {
                foreach( $this->userNames as $userName ) {
                    $user = User::newFromName( $userName );
                    if( $user && $user->isRegistered() ) {
                        $roleUsers[ 0 ][ 'users' ][ $user->getId() ] = $user;
                    }
                }
            }

            // Sort $roleUsers by priority and role name
            uasort( $roleUsers, function( array $a, array $b ) {
                $aRole = $a[ 'role' ];
                $bRole = $b[ 'role' ];

                if( !$aRole ) {
                    // If $a does not have a role, move $a down
                    return 1;
                } elseif( !$bRole ) {
                    // If $b does not have a role, move $a up
                    return -1;
                }

                if( $aRole->getPriority() === $bRole->getPriority() ) {
                    return $aRole->getName() < $bRole->getName() ? -1 : 1;
                } else {
                    return $a[ 'role' ]->getPriority() > $b[ 'role' ]->getPriority() ? -1 : 1;
                }
            } );

            $users = [];

            // Sort users within $roleUsers by display name
            foreach( $roleUsers as &$roleUserList ) {
                uasort( $roleUserList[ 'users' ], function( User $a, User $b ) use ( $useDisplayName ){
                    global $wgUserRolesUseRealName;

                    $aName = $wgUserRolesUseRealName ? $a->getRealName() : $a->getName();
                    $bName = $wgUserRolesUseRealName ? $b->getRealName() : $b->getName();

                    if( $useDisplayName ) {
                        $aUserInfo = UserRoles::getUserInfo( $a->getId() );
                        $bUserInfo = UserRoles::getUserInfo( $b->getId() );

                        $aName = $aUserInfo ? $aUserInfo->getDisplayName() : $aName;
                        $bName = $bUserInfo ? $bUserInfo->getDisplayName() : $bName;
                    }

                    return $aName < $bName ? -1 : 1;
                } );

                $users += $roleUserList[ 'users' ];
            }

            $excludedUsers = $this->getExcludedUsers();

            if( count( $excludedUsers ) ) {
                $users = array_diff_key( $users, $excludedUsers );
            }

            $this->users = $users;
        }
    }

    public static function getIdFromName( string $name ): string {
        return str_replace( ' ', '_', trim( $name ) );
    }

    protected static function explodeList( string $list ): array {
        return array_map( 'trim', explode( ',', $list ) );
    }
}