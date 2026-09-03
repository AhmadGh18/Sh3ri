<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Admin\ReportAdminController;
use App\Http\Controllers\Api\V1\Admin\SubmissionAdminController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\CommunityInteractionController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\EraController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\MeAudioUsageController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MeterController;
use App\Http\Controllers\Api\V1\PlansController;
use App\Http\Controllers\Api\V1\PoemController;
use App\Http\Controllers\Api\V1\PoetController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\TopicController;
use App\Http\Controllers\Api\V1\UserPoemController;
use App\Http\Controllers\Api\V1\VerseAudioController;
use App\Http\Controllers\Api\V1\VerseController;
use Illuminate\Support\Facades\Route;

// Named at top-level so Laravel's default VerifyEmail notification finds it
// via Route::has('verification.verify'). URL stays /api/v1/auth/email/verify/{id}/{hash}.
Route::get('v1/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Public catalog — throttled per-IP
    Route::middleware('throttle:api-public')->group(function () {
        Route::get('config', \App\Http\Controllers\Api\V1\ConfigController::class)->name('config');
        Route::get('poems', [PoemController::class, 'index'])->name('poems.index');
        Route::get('poems/{poem:slug}', [PoemController::class, 'show'])->name('poems.show');
        Route::get('poems/{poem:slug}/verses', [PoemController::class, 'verses'])->name('poems.verses');

        Route::get('poets', [PoetController::class, 'index'])->name('poets.index');
        Route::get('poets/{poet:slug}', [PoetController::class, 'show'])->name('poets.show');
        Route::get('poets/{poet:slug}/poems', [PoetController::class, 'poems'])->name('poets.poems');

        Route::get('verses/{verse:uuid}', [VerseController::class, 'show'])->name('verses.show');
        Route::get('verses/{verse:uuid}/audio', [VerseAudioController::class, 'show'])
            ->middleware('throttle:audio')
            ->name('verses.audio');

        Route::get('eras', [EraController::class, 'index'])->name('eras.index');
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('topics', [TopicController::class, 'index'])->name('topics.index');
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('meters', [MeterController::class, 'index'])->name('meters.index');

        // Plans catalogue (public — used by /plans page and the upgrade modal).
        Route::get('plans', PlansController::class)->name('plans.index');

        // Quota summary — works signed-out (guest tier) OR signed-in.
        Route::get('me/audio-usage', MeAudioUsageController::class)->name('me.audio-usage');

        // Community: public user-authored poems.
        Route::get('community/user-poems',                             [CommunityController::class, 'index'])->name('community.index');
        Route::get('community/user-poems/{userPoem:uuid}',             [CommunityController::class, 'show'])->name('community.show');
        Route::get('community/user-poems/{userPoem:uuid}/comments',    [CommunityInteractionController::class, 'listComments'])->name('community.comments.index');
    });

    Route::middleware('throttle:search')->get('search', SearchController::class)->name('search');

    // Auth endpoints — tight per-IP throttle
    Route::prefix('auth')->name('auth.')->middleware('throttle:auth')->group(function () {
        Route::post('register', RegisterController::class)->name('register');
        Route::post('login', LoginController::class)->name('login');
        Route::post('google', GoogleAuthController::class)->name('google');
        Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->name('forgot-password');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('reset-password');
        // verification.verify is registered at file top-level above.
    });

    // Authenticated endpoints
    Route::middleware(['auth:sanctum', 'throttle:api-auth'])->group(function () {
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', [LogoutController::class, 'logout'])->name('logout');
            Route::post('logout-all', [LogoutController::class, 'logoutAll'])->name('logout-all');
            Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
                ->name('verification.send');
        });

        Route::prefix('me')->name('me.')->group(function () {
            Route::get('/', [MeController::class, 'show'])->name('show');
            Route::patch('/', [MeController::class, 'update'])->name('update');
            Route::delete('/', [MeController::class, 'destroy'])->name('destroy');
            Route::get('entitlements', [MeController::class, 'entitlements'])->name('entitlements');
            Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites');
        });

        // Write endpoints — optionally require a verified email. Off in MVP
        // (open access); flip SH3RI_REQUIRE_VERIFIED_EMAIL=true to enforce.
        $writeMiddleware = config('sh3ri.security.require_verified_email') ? ['verified'] : [];

        // Favorites (polymorphic — poem/verse)
        Route::middleware($writeMiddleware)->group(function () {
            Route::post('poems/{poem:slug}/favorite',     [FavoriteController::class, 'favoritePoem'])->name('poems.favorite');
            Route::delete('poems/{poem:slug}/favorite',   [FavoriteController::class, 'unfavoritePoem'])->name('poems.unfavorite');
            Route::post('verses/{verse:uuid}/favorite',   [FavoriteController::class, 'favoriteVerse'])->name('verses.favorite');
            Route::delete('verses/{verse:uuid}/favorite', [FavoriteController::class, 'unfavoriteVerse'])->name('verses.unfavorite');
        });

        // User poems (drafts / published)
        Route::get('user-poems',                            [UserPoemController::class, 'index'])->name('user-poems.index');
        Route::get('user-poems/{userPoem:uuid}',            [UserPoemController::class, 'show'])->name('user-poems.show');
        Route::middleware($writeMiddleware)->group(function () {
            Route::post('user-poems',                           [UserPoemController::class, 'store'])->name('user-poems.store');
            Route::patch('user-poems/{userPoem:uuid}',          [UserPoemController::class, 'update'])->name('user-poems.update');
            Route::delete('user-poems/{userPoem:uuid}',         [UserPoemController::class, 'destroy'])->name('user-poems.destroy');
            Route::post('user-poems/{userPoem:uuid}/publish',   [UserPoemController::class, 'publish'])->name('user-poems.publish');
            Route::post('user-poems/{userPoem:uuid}/unpublish', [UserPoemController::class, 'unpublish'])->name('user-poems.unpublish');
        });

        // Submissions (moderation-gated content)
        Route::middleware(array_merge(['throttle:submissions'], $writeMiddleware))->group(function () {
            Route::post('submissions', [SubmissionController::class, 'store'])->name('submissions.store');
        });
        Route::get('submissions',                   [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission:uuid}', [SubmissionController::class, 'show'])->name('submissions.show');

        // Reports (users flag content; moderators handle them under /admin below)
        Route::middleware(array_merge(['throttle:submissions'], $writeMiddleware))->group(function () {
            Route::post('reports', [ReportController::class, 'store'])->name('reports.store');
        });

        // Community interactions on user-poems (writes; upvote is idempotent
        // toggle so it stays cheap on the auth-user limiter).
        Route::post('community/user-poems/{userPoem:uuid}/upvote',                     [CommunityInteractionController::class, 'toggleUpvote'])->name('community.upvote');
        Route::middleware($writeMiddleware)->group(function () {
            Route::post('community/user-poems/{userPoem:uuid}/comments',               [CommunityInteractionController::class, 'storeComment'])->name('community.comments.store');
            Route::delete('community/user-poems/{userPoem:uuid}/comments/{comment:uuid}',[CommunityInteractionController::class, 'deleteComment'])->name('community.comments.destroy');
        });

        // Admin surface — permission-gated per action
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('submissions', [SubmissionAdminController::class, 'index'])
                ->middleware('can:submission.review')->name('submissions.index');
            Route::post('submissions/{submission:uuid}/approve', [SubmissionAdminController::class, 'approve'])
                ->middleware('can:submission.approve')->name('submissions.approve');
            Route::post('submissions/{submission:uuid}/reject', [SubmissionAdminController::class, 'reject'])
                ->middleware('can:submission.reject')->name('submissions.reject');
            Route::post('submissions/{submission:uuid}/request-changes', [SubmissionAdminController::class, 'requestChanges'])
                ->middleware('can:submission.review')->name('submissions.request-changes');

            Route::get('reports', [ReportAdminController::class, 'index'])
                ->middleware('can:report.handle')->name('reports.index');
            Route::post('reports/{report:uuid}/action', [ReportAdminController::class, 'action'])
                ->middleware('can:report.handle')->name('reports.action');
        });
    });
});
