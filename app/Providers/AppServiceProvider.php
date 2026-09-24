<?php

namespace App\Providers;

use App\Models\Institution;
use App\Services\MemberPortal;
use App\Support\PlanConfig;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const INSTITUTION_CACHE_KEY = 'holy_health.institution.attrs';

    public function register(): void
    {
        $this->app->singleton(PlanConfig::class);
        $this->app->scoped(\App\Services\NetworkVolume::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Drop legacy Eloquent cache entries that can unserialize as Incomplete_Class.
        Cache::forget('holy_health.institution');

        View::composer(['layouts.staff', 'layouts.app', 'layouts.member', 'layouts.auth', 'layouts.print'], function ($view) {
            $view->with('institution', $this->institution());
        });

        View::composer('layouts.member', function ($view) {
            $member = auth('member')->user();
            if ($member === null) {
                $view->with(['memberAlerts' => collect(), 'unreadMemberAlerts' => 0]);

                return;
            }

            $alerts = once(fn () => app(MemberPortal::class)->alerts($member, 6));

            $seen = session('member_alerts_seen_at');
            $seenAt = $seen ? Carbon::parse($seen) : null;
            $unread = $alerts->filter(function (array $alert) use ($seenAt) {
                return $seenAt === null || $alert['at']->greaterThan($seenAt);
            })->count();

            $view->with([
                'memberAlerts' => $alerts,
                'unreadMemberAlerts' => $unread,
            ]);
        });
    }

    private function institution(): ?Institution
    {
        return once(function () {
            $payload = Cache::remember(self::INSTITUTION_CACHE_KEY, now()->addMinutes(10), function () {
                try {
                    $row = Institution::query()->first();

                    return $row === null ? null : $row->getAttributes();
                } catch (\Throwable) {
                    return null;
                }
            });

            if (! is_array($payload) || $payload === []) {
                return null;
            }

            return (new Institution)->newFromBuilder($payload);
        });
    }
}
