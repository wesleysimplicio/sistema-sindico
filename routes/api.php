<?php

declare(strict_types=1);

/**
 * @var \App\Core\Router $router
 *
 * Mobile-app-ready JSON endpoints.
 * Public: health + login. All others require Bearer JWT (ApiAuth).
 * Tenant scoping is enforced inside controllers via Auth::condominiumId().
 */

use App\Controllers\Api\AccessLogController;
use App\Controllers\Api\AccessWebhookController;
use App\Controllers\Api\AdoptionMetricsController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\AuthRecoveryController;
use App\Controllers\Api\BookingController;
use App\Controllers\Api\CameraController;
use App\Controllers\Api\CommonAreaController;
use App\Controllers\Api\CondominiumController;
use App\Controllers\Api\ContactController;
use App\Controllers\Api\ContractorController;
use App\Controllers\Api\DashboardController;
use App\Controllers\Api\DeliveryController;
use App\Controllers\Api\DocumentController;
use App\Controllers\Api\GateTriggerController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\IncidentController;
use App\Controllers\Api\InvitationController;
use App\Controllers\Api\InvitationGuestController;
use App\Controllers\Api\LoginInvitationController;
use App\Controllers\Api\MaintenanceController;
use App\Controllers\Api\MembershipController;
use App\Controllers\Api\MessageController;
use App\Controllers\Api\NoticeController;
use App\Controllers\Api\NotificationController;
use App\Controllers\Api\NotificationPreferenceController;
use App\Controllers\Api\PaymentController;
use App\Controllers\Api\PorterNoteController;
use App\Controllers\Api\ProfileController;
use App\Controllers\Api\ResidentController;
use App\Controllers\Api\SecurityController;
use App\Controllers\Api\SystemController;
use App\Controllers\Api\UnitController;
use App\Controllers\Api\UnitOverviewController;
use App\Controllers\Api\UserDeviceController;
use App\Controllers\Api\VehicleController;
use App\Controllers\Api\VisitorController;
use App\Middleware\ApiAuth;

$router->get('/api/health',       [HealthController::class, 'index']);
$router->post('/api/auth/login',  [AuthController::class,   'login']);

$router->post('/api/auth/forgot-password', [AuthRecoveryController::class, 'forgotPassword']);
$router->post('/api/auth/verify-code',     [AuthRecoveryController::class, 'verifyCode']);
$router->post('/api/auth/reset-password',  [AuthRecoveryController::class, 'resetPassword']);

$router->post('/api/auth/invitations/{token}/accept', [LoginInvitationController::class, 'accept']);

// Sprint 5 — public webhook (HMAC-validated, NO ApiAuth)
$router->post('/api/webhooks/access-event', [AccessWebhookController::class, 'ingest']);

// Sprint 6 — public system info (no auth)
$router->get('/api/system/version',     [SystemController::class, 'version']);
$router->get('/api/system/permissions', [SystemController::class, 'permissions']);

$router->group([ApiAuth::class], function ($router): void {
    $router->post('/api/auth/logout', [AuthController::class, 'logout']);
    $router->get('/api/auth/me',      [AuthController::class, 'me']);
    $router->get('/api/profile',      [ProfileController::class, 'show']);

    $router->get('/api/me',            [ProfileController::class, 'show']);
    $router->patch('/api/me',          [ProfileController::class, 'update']);
    $router->patch('/api/me/password', [ProfileController::class, 'changePassword']);

    $router->get('/api/memberships',         [MembershipController::class, 'index']);
    $router->post('/api/memberships/select', [MembershipController::class, 'select']);

    $router->get('/api/dashboard',           [DashboardController::class, 'index']);
    $router->get('/api/admin/metrics/adoption', [AdoptionMetricsController::class, 'index']);

    $router->get('/api/condominiums',         [CondominiumController::class, 'index']);
    $router->get('/api/condominiums/{id}',    [CondominiumController::class, 'show']);

    $router->get('/api/units',                [UnitController::class, 'index']);
    $router->get('/api/residents',            [ResidentController::class, 'index']);

    // Sprint 2 - Unit hub (scoped by condominium + unit)
    $router->get('/api/condominium/{c}/units/{u}/overview',          [UnitOverviewController::class, 'show']);

    $router->get('/api/condominium/{c}/units/{u}/residents',         [ResidentController::class, 'unitIndex']);
    $router->post('/api/condominium/{c}/units/{u}/residents',        [ResidentController::class, 'unitStore']);
    $router->delete('/api/condominium/{c}/units/{u}/residents/{rid}',[ResidentController::class, 'unitDestroy']);

    $router->get('/api/condominium/{c}/units/{u}/vehicles',          [VehicleController::class, 'index']);
    $router->post('/api/condominium/{c}/units/{u}/vehicles',         [VehicleController::class, 'store']);
    $router->patch('/api/condominium/{c}/units/{u}/vehicles/{vid}',  [VehicleController::class, 'update']);
    $router->delete('/api/condominium/{c}/units/{u}/vehicles/{vid}', [VehicleController::class, 'destroy']);

    $router->get('/api/condominium/{c}/units/{u}/contractors',       [ContractorController::class, 'index']);
    $router->post('/api/condominium/{c}/units/{u}/contractors',      [ContractorController::class, 'store']);
    $router->patch('/api/condominium/{c}/units/{u}/contractors/{id}',[ContractorController::class, 'update']);
    $router->patch('/api/condominium/{c}/units/{u}/contractors/{id}/status', [ContractorController::class, 'changeStatus']);
    $router->delete('/api/condominium/{c}/units/{u}/contractors/{id}', [ContractorController::class, 'destroy']);

    $router->get('/api/condominium/{c}/porter-notes',                [PorterNoteController::class, 'index']);
    $router->post('/api/condominium/{c}/porter-notes',               [PorterNoteController::class, 'store']);

    $router->get('/api/notices',                       [NoticeController::class, 'index']);
    $router->get('/api/notices/unread-count',          [NoticeController::class, 'unreadCount']);
    $router->get('/api/notices/{id}',                  [NoticeController::class, 'show']);
    $router->post('/api/notices',                      [NoticeController::class, 'store']);
    $router->post('/api/notices/{id}/attachments',     [NoticeController::class, 'addAttachment']);
    $router->post('/api/notices/{id}/read',            [NoticeController::class, 'markRead']);

    $router->get('/api/maintenance',                   [MaintenanceController::class, 'index']);
    $router->get('/api/maintenance/mine',              [MaintenanceController::class, 'mine']);
    $router->get('/api/maintenance/{id}',              [MaintenanceController::class, 'show']);
    $router->post('/api/maintenance',                  [MaintenanceController::class, 'store']);
    $router->patch('/api/maintenance/{id}',            [MaintenanceController::class, 'updateStatus']);
    $router->post('/api/maintenance/{id}/attachments', [MaintenanceController::class, 'addAttachment']);
    $router->get('/api/maintenance/{id}/comments',     [MaintenanceController::class, 'comments']);
    $router->post('/api/maintenance/{id}/comments',    [MaintenanceController::class, 'addComment']);

    $router->get('/api/payments',             [PaymentController::class, 'index']);
    $router->get('/api/payments/mine',        [PaymentController::class, 'mine']);
    $router->get('/api/payments/summary',     [PaymentController::class, 'summary']);
    $router->patch('/api/payments/{id}/pay',  [PaymentController::class, 'markPaid']);

    $router->get('/api/visitors',                 [VisitorController::class, 'index']);
    $router->get('/api/visitors/mine',            [VisitorController::class, 'mine']);
    $router->get('/api/visitors/history',         [VisitorController::class, 'history']);
    $router->post('/api/visitors',                [VisitorController::class, 'store']);
    $router->patch('/api/visitors/{id}',          [VisitorController::class, 'updateStatus']);
    $router->post('/api/visitors/{id}/qr',        [VisitorController::class, 'qrFor']);
    $router->post('/api/visitors/{id}/check-in',  [VisitorController::class, 'checkIn']);
    $router->post('/api/visitors/{id}/check-out', [VisitorController::class, 'checkOut']);
    $router->get('/api/visitors/qr/{token}',      [VisitorController::class, 'byQr']);

    $router->get('/api/invitations',                          [InvitationController::class, 'index']);
    $router->post('/api/invitations',                         [InvitationController::class, 'store']);
    $router->get('/api/invitations/{id}',                     [InvitationController::class, 'show']);
    $router->patch('/api/invitations/{id}',                   [InvitationController::class, 'update']);
    $router->delete('/api/invitations/{id}',                  [InvitationController::class, 'destroy']);
    $router->get('/api/invitations/{id}/guests',              [InvitationGuestController::class, 'index']);
    $router->post('/api/invitations/{id}/guests',             [InvitationGuestController::class, 'store']);
    $router->patch('/api/invitations/{id}/guests/{gid}',      [InvitationGuestController::class, 'updateStatus']);
    $router->delete('/api/invitations/{id}/guests/{gid}',     [InvitationGuestController::class, 'destroy']);

    $router->get('/api/login-invitations',         [LoginInvitationController::class, 'index']);
    $router->post('/api/login-invitations',        [LoginInvitationController::class, 'store']);
    $router->delete('/api/login-invitations/{id}', [LoginInvitationController::class, 'destroy']);

    $router->get('/api/deliveries',                  [DeliveryController::class, 'index']);
    $router->get('/api/deliveries/mine',             [DeliveryController::class, 'mine']);
    $router->get('/api/deliveries/{id}',             [DeliveryController::class, 'show']);
    $router->post('/api/deliveries',                 [DeliveryController::class, 'store']);
    $router->patch('/api/deliveries/{id}/withdraw',  [DeliveryController::class, 'withdraw']);

    $router->get('/api/common-areas',         [CommonAreaController::class, 'index']);

    $router->get('/api/bookings',             [BookingController::class, 'index']);
    $router->get('/api/bookings/mine',        [BookingController::class, 'mine']);
    $router->post('/api/bookings',            [BookingController::class, 'store']);
    $router->patch('/api/bookings/{id}',      [BookingController::class, 'updateStatus']);

    $router->get('/api/documents',                       [DocumentController::class, 'index']);
    $router->get('/api/documents/{id}',                  [DocumentController::class, 'show']);
    $router->post('/api/documents',                      [DocumentController::class, 'store']);
    $router->get('/api/documents/{id}/signed-url',       [DocumentController::class, 'signedUrl']);
    $router->get('/api/documents/{id}/download',         [DocumentController::class, 'download']);
    $router->get('/api/document-folders',                [DocumentController::class, 'folderIndex']);
    $router->get('/api/document-folders/{id}',           [DocumentController::class, 'folderShow']);
    $router->post('/api/document-folders',               [DocumentController::class, 'folderStore']);
    $router->delete('/api/document-folders/{id}',        [DocumentController::class, 'folderDestroy']);

    $router->get('/api/messages',             [MessageController::class, 'index']);
    $router->get('/api/messages/inbox',       [MessageController::class, 'inbox']);
    $router->post('/api/messages',            [MessageController::class, 'store']);
    $router->patch('/api/messages/{id}/read', [MessageController::class, 'read']);

    // Sprint 5 — access logs, cameras, gate triggers, incidents
    $router->get('/api/access-logs',                  [AccessLogController::class, 'index']);
    $router->get('/api/access-logs/{id}',             [AccessLogController::class, 'show']);

    $router->get('/api/cameras',                      [CameraController::class, 'index']);
    $router->get('/api/cameras/{id}',                 [CameraController::class, 'show']);
    $router->get('/api/cameras/{id}/stream',          [CameraController::class, 'stream']);

    $router->get('/api/gate-triggers',                [GateTriggerController::class, 'index']);
    $router->post('/api/gate-triggers/{id}/fire',     [GateTriggerController::class, 'fire']);
    $router->get('/api/gate-triggers/{id}/logs',      [GateTriggerController::class, 'logs']);

    $router->get('/api/incidents',                    [IncidentController::class, 'index']);
    $router->get('/api/incidents/{id}',               [IncidentController::class, 'show']);
    $router->post('/api/incidents',                   [IncidentController::class, 'store']);
    $router->patch('/api/incidents/{id}',             [IncidentController::class, 'update']);
    $router->get('/api/incidents/{id}/comments',      [IncidentController::class, 'comments']);
    $router->post('/api/incidents/{id}/comments',     [IncidentController::class, 'addComment']);

    $router->get('/api/incident-types',               [IncidentController::class, 'types']);
    $router->post('/api/incident-types',              [IncidentController::class, 'storeType']);

    // Sprint 6 — notifications feed
    $router->get('/api/notifications',                 [NotificationController::class, 'index']);
    $router->get('/api/notifications/unread-count',    [NotificationController::class, 'unreadCount']);
    $router->post('/api/notifications/{id}/read',      [NotificationController::class, 'read']);
    $router->post('/api/notifications/read-all',       [NotificationController::class, 'readAll']);

    // Sprint 6 — notification preferences (channel x event matrix)
    $router->get('/api/notification-preferences',      [NotificationPreferenceController::class, 'index']);
    $router->put('/api/notification-preferences',      [NotificationPreferenceController::class, 'update']);

    // Sprint 6 — push devices (FCM)
    $router->get('/api/devices',         [UserDeviceController::class, 'index']);
    $router->post('/api/devices',        [UserDeviceController::class, 'store']);
    $router->delete('/api/devices/{id}', [UserDeviceController::class, 'destroy']);

    // Sprint 6 — security (2FA + sessions)
    $router->get('/api/settings/security',                 [SecurityController::class, 'status']);
    $router->post('/api/settings/security/2fa/setup',      [SecurityController::class, 'setup2fa']);
    $router->post('/api/settings/security/2fa/enable',     [SecurityController::class, 'enable2fa']);
    $router->post('/api/settings/security/2fa/disable',    [SecurityController::class, 'disable2fa']);
    $router->get('/api/settings/sessions',                 [SecurityController::class, 'listSessions']);
    $router->delete('/api/settings/sessions/{id}',         [SecurityController::class, 'revokeSession']);

    // Sprint 6 — contact form + sindico inbox
    $router->post('/api/contact',                  [ContactController::class, 'store']);
    $router->get('/api/contact-messages',          [ContactController::class, 'index']);
    $router->get('/api/contact-messages/{id}',     [ContactController::class, 'show']);
    $router->patch('/api/contact-messages/{id}',   [ContactController::class, 'update']);
});
