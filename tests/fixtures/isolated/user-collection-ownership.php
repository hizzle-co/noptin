<?php
/** User event ownership must not depend on a license or change shared user contexts. */
namespace {
    define( 'ABSPATH', __DIR__ );
    $has_license = false;
    /** Fixture implementation for the isolated regression. */
    function noptin_has_alk() { return $GLOBALS['has_license']; }
    /** Fixture implementation for the isolated regression. */
    function add_filter( ...$args ) {}
}

namespace Hizzle\Noptin\Objects {
    /** In-memory fixture used only by this isolated regression. */
    class People {
        public $label;
        public $singular_label;
        public $type;
        public $icon;
        public $email_sender;
        public $email_sender_options;
        public $is_stand_alone = true;
        public $can_list = false;
        public $owner_at_registration;
        /** Fixture implementation for the isolated regression. */
        public function __construct() { $this->owner_at_registration = $this->integration; }
    }
}

namespace {
    require dirname( __DIR__, 3 ) . '/build/Objects/Users.php';
    foreach ( array( false, true ) as $licensed ) {
        $has_license = $licensed;
        foreach ( array( 'user' => 'wordpress-users', 'current_user' => 'wordpress', 'post_author' => 'wordpress' ) as $type => $owner ) {
            $collection = new \Hizzle\Noptin\Objects\Users( $type, $type, $type );
            if ( $collection->integration !== $owner || $collection->owner_at_registration !== $owner ) {
                throw new \RuntimeException( 'Incorrect ownership for ' . $type );
            }
            if ( ( 'wp_users' === $collection->email_sender ) !== ( $licensed && 'user' === $type ) ) {
                throw new \RuntimeException( 'User sender availability changed for ' . $type );
            }
        }
    }
    echo "User ownership, shared contexts, and sender availability passed with and without a license.\n";
}
