<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\JournalController;
use App\Http\Controllers\Api\V1\JournalManage\AnnouncementController as JournalManageAnnouncementController;
use App\Http\Controllers\Api\V1\JournalManage\DashboardController as JournalManageDashboardController;
use App\Http\Controllers\Api\V1\JournalManage\ReviewerRequestController as JournalManageReviewerRequestController;
use App\Http\Controllers\Api\V1\JournalManage\SubmissionController as JournalManageSubmissionController;
use App\Http\Controllers\Api\V1\JournalManage\VolumeController as JournalManageVolumeController;
use App\Http\Controllers\Api\V1\MembershipController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductionController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('api.enabled')
    ->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('login', [AuthController::class, 'login'])
                ->middleware('throttle:5,1');

            Route::middleware('auth:sanctum')->group(function () {
                Route::get('user', [AuthController::class, 'user']);
                Route::post('logout', [AuthController::class, 'logout']);
                Route::post('verify-email', [AuthController::class, 'verifyEmail']);
                Route::post('resend-verification', [AuthController::class, 'resendVerification'])
                    ->middleware('throttle:3,10');
            });
        });

        Route::get('journals/picker', [JournalController::class, 'picker']);
        Route::get('journals', [JournalController::class, 'index']);
        Route::get('journals/{journal:slug}', [JournalController::class, 'show']);
        Route::get('journals/{journal:slug}/browse', [JournalController::class, 'browse']);

        Route::get('articles', [ArticleController::class, 'index']);

        Route::middleware('optional.sanctum')->group(function () {
            Route::get('journals/{journal:slug}/articles/{article:slug}', [ArticleController::class, 'show']);
        });

        Route::middleware(['auth:sanctum', 'verified'])->prefix('me')->group(function () {
            Route::get('submissions/create-options', [SubmissionController::class, 'createOptions']);
            Route::get('submissions', [SubmissionController::class, 'index']);
            Route::post('submissions', [SubmissionController::class, 'store']);
            Route::get('submissions/{submission}', [SubmissionController::class, 'show']);
            Route::post('submissions/{submission}/resubmit', [SubmissionController::class, 'resubmit']);
            Route::post('submissions/{submission}/pay-submission-fee', [SubmissionController::class, 'paySubmissionFee']);
            Route::post('submissions/{submission}/pay-publication-fee', [SubmissionController::class, 'payPublicationFee']);

            Route::get('memberships', [MembershipController::class, 'index']);
            Route::get('payments', [PaymentController::class, 'index']);
            Route::post('payments/verify', [PaymentController::class, 'verify']);
            Route::get('payments/{paymentTransaction}', [PaymentController::class, 'show']);
            Route::get('payments/{paymentTransaction}/receipt', [PaymentController::class, 'downloadReceipt']);
            Route::post('payments/articles/{journal:slug}/{article:slug}/purchase', [PaymentController::class, 'purchaseArticle']);
            Route::post('payments/memberships/{plan}/purchase', [PaymentController::class, 'purchaseMembership']);
            Route::post('payments/journals/{journal:slug}/activate', [PaymentController::class, 'activateJournal']);

            Route::middleware('review.queue')->prefix('reviews')->group(function () {
                Route::get('/', [ReviewController::class, 'index']);
                Route::get('{submission}', [ReviewController::class, 'show']);
                Route::post('{submission}/decide', [ReviewController::class, 'decide']);
                Route::get('{submission}/document', [ReviewController::class, 'downloadDocument']);
                Route::get('{submission}/revisions/{revision}/document', [ReviewController::class, 'downloadRevision']);
            });

            Route::middleware('production.queue')->prefix('production/queue')->group(function () {
                Route::get('/', [ProductionController::class, 'index']);
                Route::get('{submission}', [ProductionController::class, 'show']);
                Route::post('{submission}/start', [ProductionController::class, 'start']);
                Route::post('{submission}/upload', [ProductionController::class, 'upload']);
                Route::post('{submission}/complete', [ProductionController::class, 'complete']);
                Route::get('{submission}/source-document', [ProductionController::class, 'downloadSource']);
                Route::get('{submission}/production-document', [ProductionController::class, 'downloadProduction']);
            });

            Route::middleware('journal.manage.api')->prefix('journals/{journal:slug}/manage')->group(function () {
                Route::get('dashboard', JournalManageDashboardController::class);
                Route::get('submissions/form-options', [JournalManageSubmissionController::class, 'formOptions']);
                Route::get('submissions', [JournalManageSubmissionController::class, 'index']);
                Route::get('submissions/{submission}', [JournalManageSubmissionController::class, 'show']);
                Route::post('submissions/{submission}/assign-reviewer', [JournalManageSubmissionController::class, 'assignReviewer']);
                Route::post('submissions/{submission}/review-type', [JournalManageSubmissionController::class, 'updateReviewType']);
                Route::post('submissions/{submission}/publish', [JournalManageSubmissionController::class, 'publish']);
                Route::get('reviewer-requests', [JournalManageReviewerRequestController::class, 'index']);
                Route::post('reviewer-requests/{reviewerRequest}/approve', [JournalManageReviewerRequestController::class, 'approve']);
                Route::post('reviewer-requests/{reviewerRequest}/reject', [JournalManageReviewerRequestController::class, 'reject']);
                Route::get('announcements', [JournalManageAnnouncementController::class, 'index']);
                Route::post('announcements', [JournalManageAnnouncementController::class, 'store']);
                Route::post('announcements/{announcement}/close', [JournalManageAnnouncementController::class, 'close']);
                Route::get('volumes', [JournalManageVolumeController::class, 'index']);
            });
        });
    });
