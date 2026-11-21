<?php
namespace Hizzle\Noptin\Emails\Bulk;

/** A queue operation is occupied or needs explicit recovery. */
class Reservation_Exception extends \RuntimeException {

	public $wait;

	public function __construct( $message, $wait = false ) {
		parent::__construct( $message );
		$this->wait = $wait;
	}
}
