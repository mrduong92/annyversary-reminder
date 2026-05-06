<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->defineSubscriptionGates();
    }

    private function defineSubscriptionGates(): void
    {
        $svc = fn () => app(SubscriptionService::class);

        Gate::define('upload-document',   fn (User $u) => $svc()->canUploadDocument($u));
        Gate::define('share-chat',        fn (User $u) => $svc()->canCreateShare($u));
        Gate::define('import-image',      fn (User $u) => $svc()->canImportImage($u));
        Gate::define('use-lunar-special-days',  fn (User $u) => $svc()->canUseLunarSpecialDays($u));
        Gate::define('generate-prayer',   fn (User $u) => $svc()->canGeneratePrayer($u));
        Gate::define('crud-events-ai',    fn (User $u) => $svc()->canCrudEventsViaAi($u));
        Gate::define('send-agent-message',fn (User $u) => $svc()->canSendAgentMessage($u));
    }
}
