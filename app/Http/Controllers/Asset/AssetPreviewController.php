<?php

namespace App\Http\Controllers\Asset;

use App\Models\Asset;
use App\Models\Merchant;
use App\Models\PermissionModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssetPreviewController
{
    public function show(Request $request, string $id)
    {
        $asset = Asset::query()
            ->with([
                'merchant.logo',
                'merchant.settings',
                'business',
                'branch',
                'assetType',
                'assignedUser',
                'vendor',
                'createdBy',
                'attachment',
            ])
            ->find($id);

        if (! $asset) {
            throw new NotFoundHttpException;
        }

        $user = $this->authorizeView($asset);

        [$previousAssetUrl, $nextAssetUrl] = $this->adjacentAssetUrls($asset, $user);

        return view('filament.pages.asset-preview', [
            'record' => $asset,
            'previousAssetUrl' => $previousAssetUrl,
            'nextAssetUrl' => $nextAssetUrl,
            'closeUrl' => $this->resolveCloseUrl(),
        ]);
    }

    protected function authorizeView(Asset $asset): Merchant|User
    {
        $user = auth('staff')->user() ?? auth('merchant')->user();

        if (! $user) {
            throw new AccessDeniedHttpException;
        }

        $merchantId = $user instanceof Merchant ? $user->id : $user->merchant_id;

        if ((string) $asset->merchant_id !== (string) $merchantId) {
            throw new AccessDeniedHttpException;
        }

        if (! PermissionModule::isEnabledForMerchant('assets', (string) $merchantId)) {
            throw new AccessDeniedHttpException;
        }

        $guard = auth('staff')->check() ? 'staff' : 'merchant';

        $hasAssetViewPermission = $this->userHasPermission($user, 'assets.view', $guard);

        if (! $hasAssetViewPermission && $user instanceof User) {
            $hasAssetViewPermission = $this->userHasPermission($user, 'assets.view', 'merchant');
        }

        if (! $hasAssetViewPermission) {
            throw new AccessDeniedHttpException;
        }

        if ($user instanceof User) {
            $hasBusiness = $user->businesses()->where('businesses.id', $asset->business_id)->exists();
            $hasBranch = $user->branches()->where('branches.id', $asset->branch_id)->exists();

            if (! $hasBusiness || ! $hasBranch) {
                throw new AccessDeniedHttpException;
            }
        }

        return $user;
    }

    protected function userHasPermission(Merchant|User $user, string $permission, string $guard): bool
    {
        try {
            return $user->hasPermissionTo($permission, $guard);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    protected function resolveCloseUrl(): string
    {
        if (auth('staff')->check()) {
            return route('filament.user.resources.assets.index');
        }

        if (auth('merchant')->check()) {
            return route('filament.merchant.resources.assets.index');
        }

        return url('/');
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function adjacentAssetUrls(Asset $asset, Merchant|User $user): array
    {
        $idsQuery = Asset::query()->where('merchant_id', $asset->merchant_id);

        if ($user instanceof User) {
            $idsQuery
                ->whereHas('business.users', fn (Builder $query) => $query->where('users.id', $user->id))
                ->whereHas('branch.users', fn (Builder $query) => $query->where('users.id', $user->id));
        }

        $ids = $idsQuery
            ->orderBy('asset_code')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $currentIndex = $ids->search((string) $asset->id);

        if ($currentIndex === false) {
            return [null, null];
        }

        $buildUrl = fn (?string $assetId): ?string => $assetId
            ? route('assets.preview', ['id' => $assetId])
            : null;

        return [
            $buildUrl($ids->get($currentIndex - 1)),
            $buildUrl($ids->get($currentIndex + 1)),
        ];
    }
}
