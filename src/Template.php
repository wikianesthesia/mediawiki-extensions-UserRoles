<?php

namespace MediaWiki\Extension\UserRoles;

class Template {
    /**
     * @var string
     */
    protected $id = '';

    protected $templates = [
        'list' => '',
        'user' => ''
    ];

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

        $this->id = trim( $m[ 1 ] );

        $options = trim( $m[ 2 ] );

        foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
            $arr = preg_split( '/\s*=\s*/', $option, 2 );
            $option = $arr[ 0 ];

            if ( isset( $arr[ 1 ] ) ) {
                $value = $arr[ 1 ];

                switch( $option ) {
                    case 'list':
                        $this->templates[ 'list' ] = $value;
                        break;
                    case 'user':
                        $this->templates[ 'user' ] = $value;
                        break;
                }
            }
        }
    }

    public function getId(): string {
        return $this->id;
    }

    public function getListTemplate(): string {
        return $this->templates[ 'list' ];
    }

    public function getUserTemplate(): string {
        return $this->templates[ 'user' ];
    }
}