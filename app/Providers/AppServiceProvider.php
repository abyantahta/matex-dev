<?php

namespace App\Providers;

use App\Models\DeliveryNote;
use App\Models\DeliverySchedule;
use App\Models\Forecast;
use App\Models\PurchaseOrder;
use App\Policies\DeliveryNotePolicy;
use App\Policies\DeliverySchedulePolicy;
use App\Policies\ForecastPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Services\Qad\QadClientInterface;
use App\Services\Qad\StubQadClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(QadClientInterface::class, StubQadClient::class);
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(DeliveryNote::class, DeliveryNotePolicy::class);
        Gate::policy(DeliverySchedule::class, DeliverySchedulePolicy::class);
        Gate::policy(Forecast::class, ForecastPolicy::class);
    }
}
