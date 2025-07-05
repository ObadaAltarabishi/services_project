<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\File;
use App\Models\Image;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Profile;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Policies\AdminPolicy;
use App\Policies\FilePolicy;
use App\Policies\ImagePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProfilePolicy;
use App\Policies\ServicePolicy;
use App\Policies\UserPolicy;
use App\Policies\WalletPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Wallet::class => WalletPolicy::class,
        Profile::class => ProfilePolicy::class,
        Service::class => ServicePolicy::class,
        Image::class => ImagePolicy::class,
        Order::class => OrderPolicy::class,
        File::class => FilePolicy::class,
        Notification::class => NotificationPolicy::class,
        Admin::class => AdminPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // تعريف بوابة عامة للتحقق من ملكية الموارد
        Gate::define('update-resource', function ($user, $model) {
            return $user->id === $model->user_id;
        });

        // تعريف بوابات خاصة لكل نموذج
        Gate::define('update-user', [UserPolicy::class, 'update']);
        Gate::define('delete-user', [UserPolicy::class, 'delete']);
        
        Gate::define('view-wallet', [WalletPolicy::class, 'view']);
        Gate::define('update-wallet', [WalletPolicy::class, 'update']);
        
        Gate::define('view-profile', [ProfilePolicy::class, 'view']);
        Gate::define('update-profile', [ProfilePolicy::class, 'update']);
        
        Gate::define('update-service', [ServicePolicy::class, 'update']);
        Gate::define('delete-service', [ServicePolicy::class, 'delete']);
        
        Gate::define('delete-image', [ImagePolicy::class, 'delete']);
        
        Gate::define('view-order', [OrderPolicy::class, 'view']);
        Gate::define('update-order', [OrderPolicy::class, 'update']);
        Gate::define('delete-order', [OrderPolicy::class, 'delete']);
        
        Gate::define('view-file', [FilePolicy::class, 'view']);
        Gate::define('delete-file', [FilePolicy::class, 'delete']);
        
        Gate::define('view-notification', [NotificationPolicy::class, 'view']);
        Gate::define('update-notification', [NotificationPolicy::class, 'update']);
        Gate::define('delete-notification', [NotificationPolicy::class, 'delete']);

        Gate::define('admin-action', function ($user) {
           return $user->role === 'admin'; // Assuming you have 'role' column in users table
        });

        Gate::define('admin-action', function ($user) {
    return $user->isAdmin();
        });

        Gate::define('view-service', function ($user, Service $service) {
            return $user->id === $service->user_id;
        });

        Gate::define('update-service', function ($user, Service $service) {
            return $user->isAdmin() || $user->id === $service->user_id;
        });

        Gate::define('delete-service', function ($user, Service $service) {
            return $user->isAdmin() || $user->id === $service->user_id;
        });

        

    }
}