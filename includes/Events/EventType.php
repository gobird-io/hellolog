<?php
/**
 * Canonical event-type verbs.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Events;

defined( 'ABSPATH' ) || exit;

/**
 * Verb strings used in the event payload's `event_type` field. The pair
 * `(object, event_type)` is the broad classifier the backend UI groups by.
 */
final class EventType {

	public const CREATED             = 'created';
	public const UPDATED             = 'updated';
	public const DELETED             = 'deleted';
	public const RESTORED            = 'restored';
	public const PUBLISHED           = 'published';
	public const UNPUBLISHED         = 'unpublished';
	public const ACTIVATED           = 'activated';
	public const DEACTIVATED         = 'deactivated';
	public const INSTALLED           = 'installed';
	public const UNINSTALLED         = 'uninstalled';
	public const UPDATED_FILE        = 'file-updated';
	public const LOGIN               = 'login';
	public const LOGOUT              = 'logout';
	public const FAILED_LOGIN        = 'failed-login';
	public const PASSWORD_RESET      = 'password-reset';
	public const PROFILE_UPDATED     = 'profile-updated';
	public const ROLE_CHANGED        = 'role-changed';
	public const SUBMITTED           = 'submitted';
	public const APPROVED            = 'approved';
	public const REJECTED            = 'rejected';
	public const SPAM                = 'spam';
	public const TRASHED             = 'trashed';
	public const VIEWED              = 'viewed';
	public const NOT_FOUND           = 'not-found';
	public const TWO_FACTOR_ENABLED  = '2fa-enabled';
	public const TWO_FACTOR_DISABLED = '2fa-disabled';
}
