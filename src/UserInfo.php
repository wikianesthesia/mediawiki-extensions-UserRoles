<?php

namespace MediaWiki\Extension\UserRoles;

use User;

class UserInfo {

    /**
     * @var string
     */
    protected $displayName = '';

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var string
     */
    protected $imageFile = '';

    /**
     * @var string
     */
    protected $userName = '';

    /**
     * @var User
     */
    protected $user;

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

        $this->userName = trim( $m[ 1 ] );
        $this->user = User::newFromName( $this->userName );

        if( !$this->user->isRegistered() ) {
            return false;
        }

        $this->id = $this->user->getId();

        $options = trim( $m[ 2 ] );

        foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
            $arr = preg_split( '/\s*=\s*/', $option, 2 );
            $option = $arr[ 0 ];

            if ( isset( $arr[ 1 ] ) ) {
                $value = $arr[ 1 ];

                switch( $option ) {
                    case 'displayname':
                        $this->displayName = $value;
                        break;
                    case 'imagefile':
                        $this->imageFile = $value;
                        break;
                }
            }
        }
    }

    public function getDisplayName(): string {
        global $wgUserRolesUseRealName;

        if( !$this->displayName ) {
            if( $wgUserRolesUseRealName && $this->getRealName() ) {
                $this->displayName = $this->getRealName();
            } else {
                $this->displayName = $this->getUserName();
            }
        }

        return $this->displayName;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getImageFile(): string {
        return $this->imageFile;
    }

    public function getRealName(): string {
        return $this->user->getRealName();
    }

    public function getUserName(): string {
        return $this->userName;
    }
}