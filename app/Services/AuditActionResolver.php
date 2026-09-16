<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditActionResolver
{
    protected array $identityKeys = [
        'name', 'email', 'title', 'code', 'reference', 'label', 'full_name', 'business_name',
    ];

    protected array $verbTemplates = [
        'store' => 'Created {label}',
        'update' => 'Updated {label}',
        'destroy' => 'Deleted {label}',
        'bulk-destroy' => 'Bulk-deleted {count} {labels}',
        'toggle' => 'Toggled {label} status',
        'approve' => 'Approved {label}',
        'reject' => 'Rejected {label}',
        'retry' => 'Retried {label}',
        'reconcile' => 'Reconciled {label}',
        'refund' => 'Recorded refund for {label}',
        'adjust' => 'Adjusted wallet balance for {label}',
        'review' => 'Reviewed {label}',
        'status' => 'Updated status of {label}',
        'proof' => 'Uploaded a delivery proof for {label}',
        'read' => 'Marked {label} as read',
        'generate' => 'Generated {label}',
    ];

    protected array $resourceLabels = [
        'partners' => 'partner',
        'users' => 'user',
        'drivers' => 'driver',
        'orders' => 'order',
        'payments' => 'payment',
        'wallets' => 'wallet',
        'wallet-top-ups' => 'wallet top-up',
        'services' => 'service',
        'vouchers' => 'voucher',
        'mission-templates' => 'mission template',
        'mission-rewards' => 'mission reward payout',
        'registration-fees' => 'registration fee',
        'notifications' => 'notification',
        'document-verifications' => 'document verification',
        'security-settings' => 'security setting',
        'security-events' => 'security event',
        'audit-logs' => 'audit log',
        'roles' => 'administrator role',
        'tickets' => 'support ticket',
        'ratings' => 'rating/review',
        'reports' => 'report',
        'wallets.top-ups' => 'wallet top-up',
        'registration-fees.rider-prices' => 'rider registration price',
        'security-settings.settings' => 'security setting',
        'security-settings.rate-limits' => 'security rate limit',
        'security-settings.ip-rules' => 'IP rule',
        'security-settings.retention' => 'audit log retention policy',
        'tickets.messages' => 'ticket message',
        'drivers.documents' => 'driver document',
        'partners.menu.categories' => 'menu category',
        'partners.menu.items' => 'menu item',
        'partners.staff' => 'staff member',
        'faq.categories' => 'FAQ category',
        'faq.articles' => 'FAQ article',
        'profile.sessions' => 'administrator session',
        'profile' => 'administrator profile',
    ];

    protected array $specialDescriptions = [
        'admin.logout' => 'Signed out of the admin panel',
        'admin.profile.update' => 'Updated administrator profile',
        'admin.profile.password' => 'Changed administrator password',
        'admin.profile.sessions.destroy' => 'Revoked an active administrator session',
        'admin.2fa.enable' => 'Enabled two-factor authentication',
        'admin.2fa.disable' => 'Disabled two-factor authentication',
        'admin.2fa.regenerate' => 'Regenerated two-factor recovery codes',
        'admin.security-settings.ip-rules.store' => 'Added an IP allow/deny rule',
        'admin.security-settings.ip-rules.toggle' => 'Toggled an IP allow/deny rule',
        'admin.security-settings.ip-rules.destroy' => 'Removed an IP allow/deny rule',
        'admin.security-settings.rate-limits.update' => 'Updated security rate limit policy',
        'admin.security-settings.retention.update' => 'Updated audit log retention policy',
    ];

    public function resolve(Request $request, ?Model $resource, array $newValues, ?array $oldValues, string $action): array
    {
        [$verb, $path] = $this->parseAction($action);

        $description = $this->specialDescriptions[$action]
            ?? $this->describeVerbalAction($action, $verb, $path, $resource, $newValues);

        if ($verb === 'update' && ! empty($oldValues)) {
            $changed = $this->changedFields($oldValues, $newValues);
            if ($changed) {
                $description .= ' — changed: '.implode(', ', $changed);
            }
        }

        return [
            'description' => $description,
            'event_category' => $this->categoryFor($action, $verb, $path),
            'risk_level' => $this->riskFor($action, $verb, $path),
        ];
    }

    public function describeForDisplay(AuditLog $log): string
    {
        if (! empty($log->description)) {
            return $log->description;
        }

        $action = $log->action ?? '';
        [$verb, $path] = $this->parseAction($action);

        if (isset($this->specialDescriptions[$action])) {
            return $this->specialDescriptions[$action];
        }

        $newValues = (array) $log->new_values;
        $oldValues = (array) $log->old_values;

        $resource = $this->modelFrom($log->resource_type, $log->resource_id);
        $description = $this->describeVerbalAction($action, $verb, $path, $resource, $newValues);

        if ($verb === 'update' && ! empty($oldValues)) {
            $changed = $this->changedFields($oldValues, $newValues);
            if ($changed) {
                $description .= ' — changed: '.implode(', ', $changed);
            }
        }

        return $description;
    }

    protected function describeVerbalAction(string $action, string $verb, string $path, ?Model $resource, array $newValues): string
    {
        $label = $this->resourceLabel($path);
        $template = $this->verbTemplates[$verb] ?? null;

        if ($verb === 'waive') {
            return ucfirst($this->appendEntity('Waived registration fee', $newValues, $resource));
        }

        if ($path === 'roles' && $verb === 'update') {
            $entity = $this->entityName($newValues, $resource);

            return $entity !== null ? "Updated role permissions for '{$entity}'" : 'Updated role permissions';
        }

        if ($template === null) {
            return 'Performed '.Str::replace(['-', '_', '.'], ' ', Str::snake($verb)).' on '.$label;
        }

        if ($verb === 'bulk-destroy' && isset($newValues['ids']) && is_array($newValues['ids'])) {
            $ids = array_values(array_filter($newValues['ids'], 'is_numeric'));
            $count = count($ids);
            $shown = array_slice($ids, 0, 5);
            $suffix = count($ids) > 5 ? ', +'.(count($ids) - 5).' more' : '';
            $phrase = str_replace(['{count}', '{labels}'], [max($count, 1), Str::plural($label)], $template);
            $phrase .= ' (IDs: '.implode(', ', $shown).$suffix.')';

            return ucfirst($phrase);
        }

        $phrase = str_replace('{label}', $label, $template);

        return ucfirst($this->appendEntity($phrase, $newValues, $resource));
    }

    protected function appendEntity(string $phrase, array $newValues, ?Model $resource): string
    {
        if (str_contains($phrase, '{count}')) {
            return $phrase;
        }

        $entity = $this->entityName($newValues, $resource);
        $id = $this->entityId($newValues, $resource);

        if ($entity !== null) {
            $phrase .= " '{$entity}'";
        }
        if ($id !== null) {
            $phrase .= " (#{$id})";
        }

        return $phrase;
    }

    protected function parseAction(string $action): array
    {
        $segments = explode('.', $action);
        if (($segments[0] ?? null) === 'admin') {
            array_shift($segments);
        }

        if (count($segments) < 2) {
            return [implode('.', $segments), ''];
        }

        $verb = array_pop($segments);

        return [$verb, implode('.', $segments)];
    }

    protected function resourceLabel(string $path): string
    {
        if (isset($this->resourceLabels[$path])) {
            return $this->resourceLabels[$path];
        }

        $segments = explode('.', $path);
        $last = end($segments);

        $plural = [
            'categories' => 'category', 'items' => 'item', 'staff' => 'staff member',
        ];

        return $plural[$last] ?? Str::singular(Str::replace('-', ' ', $last));
    }

    protected function entityName(array $newValues, ?Model $resource): ?string
    {
        foreach ($this->identityKeys as $key) {
            if (isset($newValues[$key]) && is_scalar($newValues[$key]) && trim((string) $newValues[$key]) !== '') {
                return trim((string) $newValues[$key]);
            }
        }

        if ($resource !== null) {
            foreach ($this->identityKeys as $key) {
                $value = $resource->getAttribute($key);
                if (is_scalar($value) && ! empty(trim((string) $value))) {
                    return trim((string) $value);
                }
            }
        }

        return null;
    }

    protected function entityId(array $newValues, ?Model $resource): int|string|null
    {
        if ($resource !== null && $resource->getKey() !== null) {
            return $resource->getKey();
        }

        return $newValues['id'] ?? null;
    }

    protected function changedFields(array $oldValues, array $newValues): array
    {
        $ignored = [
            '_token', '_method', 'password', 'password_confirmation', 'two_factor_secret',
            'logo', 'cover_photo', 'remove_logo', 'remove_cover_photo',
        ];

        $changed = [];

        foreach ($newValues as $key => $value) {
            if (in_array($key, $ignored, true) || ! array_key_exists($key, $oldValues)) {
                continue;
            }

            $old = $oldValues[$key];
            if (! $this->valuesDiffer($old, $value)) {
                continue;
            }

            $changed[] = $this->humanizeKey($key);
        }

        return $changed;
    }

    protected function valuesDiffer(mixed $old, mixed $new): bool
    {
        if (is_bool($old) || is_bool($new)) {
            return (bool) $old !== (bool) $new;
        }

        if ($old === null || $new === null) {
            return $old !== $new;
        }

        if (! is_scalar($old) || ! is_scalar($new)) {
            return json_encode($old) !== json_encode($new);
        }

        return trim((string) $old) !== trim((string) $new);
    }

    protected function humanizeKey(string $key): string
    {
        $labels = [
            'delivery_fee' => 'delivery fee', 'is_open' => 'open status', 'status' => 'status',
            'role' => 'role', 'is_active' => 'active status', 'price' => 'price',
            'is_featured' => 'featured status', 'is_verified' => 'verified status',
            'registration_fee' => 'registration fee',
        ];

        return $labels[$key] ?? Str::replace('_', ' ', $key);
    }

    protected function categoryFor(string $action, string $verb, string $path): string
    {
        return match (true) {
            $action === 'admin.logout' => 'authentication',
            Str::startsWith($path, 'security-settings'), Str::startsWith($path, 'security-events'),
            $path === '2fa', $path === 'profile.sessions' => 'security',
            $action === 'admin.profile.password' => 'security',
            $action === 'admin.profile.update' => 'user_activity',
            $path === 'roles' => 'authorization',
            default => 'data_modification',
        };
    }

    protected function riskFor(string $action, string $verb, string $path): string
    {
        return match (true) {
            in_array($verb, ['destroy', 'bulk-destroy'], true) => 'high',
            Str::startsWith($path, 'security-settings'), Str::startsWith($path, 'security-events'),
            $path === '2fa', $path === 'profile.sessions', $action === 'admin.profile.password',
            $path === 'roles' => 'high',
            default => 'low',
        };
    }

    protected function modelFrom(?string $resourceType, int|string|null $resourceId): ?Model
    {
        if ($resourceType === null || $resourceId === null) {
            return null;
        }

        $modelClass = 'App\\Models\\'.$resourceType;

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $model = (new $modelClass)->find($resourceId);
        if ($model instanceof Model) {
            $model->makeHidden(['password', 'two_factor_secret']);
        }

        return $model;
    }
}