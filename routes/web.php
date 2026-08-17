<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ArticleAdminController;
use App\Http\Controllers\Admin\EditorialBoardAdminController;
use App\Http\Controllers\Admin\IssueAdminController;
use App\Http\Controllers\Admin\JournalAdminController;
use App\Http\Controllers\Admin\JournalTeamAdminController;
use App\Http\Controllers\Admin\MembershipPlanAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Admin\SubmissionAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\VolumeAdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Author\SubmissionController as AuthorSubmissionController;
use App\Http\Controllers\CatalogCoverController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Member\ReviewerRequestController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\JournalManage\AnnouncementController as JournalManageAnnouncementController;
use App\Http\Controllers\JournalManage\ArticleController as JournalManageArticleController;
use App\Http\Controllers\JournalManage\DashboardController as JournalManageDashboardController;
use App\Http\Controllers\JournalManage\EditorialBoardController as JournalManageEditorialBoardController;
use App\Http\Controllers\JournalManage\MembershipPlanController as JournalManageMembershipPlanController;
use App\Http\Controllers\JournalManage\SettingsController as JournalManageSettingsController;
use App\Http\Controllers\JournalManage\SubmissionController as JournalManageSubmissionController;
use App\Http\Controllers\JournalManage\VolumeController as JournalManageVolumeController;
use App\Http\Controllers\JournalManage\ReviewerRequestController as JournalManageReviewerRequestController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reviewer\ReviewController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/j/{journal}/sitemap.xml', [SitemapController::class, 'journal'])->name('sitemap.journal');

Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
Route::get('/j/{journal}', [JournalController::class, 'show'])->name('journals.show');
Route::get('/j/{journal}/archive', [JournalController::class, 'archive'])->name('journals.archive');
Route::get('/j/{journal}/about', [JournalController::class, 'about'])->name('journals.about');
Route::get('/j/{journal}/editorial-board', [JournalController::class, 'editorialBoard'])->name('journals.editorial-board');
Route::get('/j/{journal}/reviewers', [JournalController::class, 'reviewers'])->name('journals.reviewers');
Route::get('/j/{journal}/browse', [JournalController::class, 'browse'])->name('journals.browse');
Route::get('/j/{journal}/announcements', [JournalController::class, 'announcements'])->name('journals.announcements');
Route::get('/j/{journal}/announcements/{announcement}', [JournalController::class, 'announcement'])->name('journals.announcements.show');
Route::get('/j/{journal}/issues/{issue}', [JournalController::class, 'issue'])->name('journals.issues.show');
Route::get('/j/{journal}/volumes/{volume}/cover', [CatalogCoverController::class, 'volume'])->name('journals.volumes.cover');
Route::get('/j/{journal}/issues/{issue}/cover', [CatalogCoverController::class, 'issue'])->name('journals.issues.cover');

Route::get('/j/{journal}/articles/{article}/pdf', [ArticleController::class, 'pdfViewer'])
    ->name('journals.articles.pdf-viewer');
Route::get('/j/{journal}/articles/{articleSlug}.pdf', [ArticleController::class, 'pdf'])
    ->name('journals.articles.pdf');
Route::get('/j/{journal}/articles/{article}', [ArticleController::class, 'show'])->name('journals.articles.show');

Route::post('/paystack/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');
Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/journals/check-slug', [JournalAdminController::class, 'checkSlug'])->name('journals.check-slug');
    Route::post('/j/{journal}/reviewer-request', [ReviewerRequestController::class, 'store'])
        ->name('reviewer-requests.store');
    Route::delete('/j/{journal}/reviewer-request', [ReviewerRequestController::class, 'destroy'])
        ->name('reviewer-requests.destroy');

    Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/j/{journal}/articles/{article}/buy', [PaymentController::class, 'buyArticle'])
        ->middleware('throttle:10,1')
        ->name('payments.articles.buy');
    Route::post('/memberships/{plan}/buy', [PaymentController::class, 'buyMembership'])
        ->middleware('throttle:10,1')
        ->name('payments.memberships.buy');

    Route::prefix('author')->name('author.')->group(function () {
        Route::get('/submissions', [AuthorSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/submissions/create', [AuthorSubmissionController::class, 'create'])->name('submissions.create');
        Route::post('/submissions', [AuthorSubmissionController::class, 'store'])->name('submissions.store');
        Route::get('/submissions/{submission}', [AuthorSubmissionController::class, 'show'])->name('submissions.show');
        Route::post('/submissions/{submission}/resubmit', [AuthorSubmissionController::class, 'resubmit'])->name('submissions.resubmit');
    });

    Route::prefix('reviewer')->name('reviewer.')->middleware('review.queue')->group(function () {
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{submission}', [ReviewController::class, 'show'])->name('reviews.show');
        Route::get('/reviews/{submission}/download', [ReviewController::class, 'download'])->name('reviews.download');
        Route::get('/reviews/{submission}/revisions/{revision}/download', [ReviewController::class, 'downloadRevision'])->name('reviews.revisions.download');
        Route::post('/reviews/{submission}/decide', [ReviewController::class, 'decide'])->name('reviews.decide');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/journals', [JournalAdminController::class, 'index'])->name('journals.index');
        Route::get('/journals/create', [JournalAdminController::class, 'create'])->name('journals.create');
        Route::post('/journals', [JournalAdminController::class, 'store'])->name('journals.store');
        Route::get('/journals/{journal}/edit', [JournalAdminController::class, 'edit'])->name('journals.edit');
        Route::put('/journals/{journal}', [JournalAdminController::class, 'update'])->name('journals.update');
        Route::delete('/journals/{journal}', [JournalAdminController::class, 'destroy'])->name('journals.destroy');

        Route::post('/journals/{journal}/team', [JournalTeamAdminController::class, 'store'])->name('journals.team.store');
        Route::put('/journals/{journal}/team/{user}', [JournalTeamAdminController::class, 'update'])->name('journals.team.update');
        Route::delete('/journals/{journal}/team/{user}', [JournalTeamAdminController::class, 'destroy'])->name('journals.team.destroy');

        Route::post('/journals/{journal}/editorial-board', [EditorialBoardAdminController::class, 'store'])->name('editorial-board.store');
        Route::delete('/journals/{journal}/editorial-board/{member}', [EditorialBoardAdminController::class, 'destroy'])->name('editorial-board.destroy');

        Route::get('/journals/{journal}/volumes', [VolumeAdminController::class, 'index'])->name('volumes.index');
        Route::post('/journals/{journal}/volumes', [VolumeAdminController::class, 'store'])->name('volumes.store');
        Route::put('/journals/{journal}/volumes/{volume}', [VolumeAdminController::class, 'update'])->name('volumes.update');

        Route::post('/journals/{journal}/volumes/{volume}/issues', [IssueAdminController::class, 'store'])->name('issues.store');
        Route::put('/journals/{journal}/volumes/{volume}/issues/{issue}', [IssueAdminController::class, 'update'])->name('issues.update');

        Route::get('/articles', [ArticleAdminController::class, 'index'])->name('articles.index');
        Route::get('/articles/create', [ArticleAdminController::class, 'create'])->name('articles.create');
        Route::post('/articles/extract', [ArticleAdminController::class, 'extract'])->name('articles.extract');
        Route::post('/articles/quick-volume', [ArticleAdminController::class, 'quickVolume'])->name('articles.quick-volume');
        Route::post('/articles/quick-issue', [ArticleAdminController::class, 'quickIssue'])->name('articles.quick-issue');
        Route::post('/articles/quick-category', [ArticleAdminController::class, 'quickCategory'])->name('articles.quick-category');
        Route::post('/articles', [ArticleAdminController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}/edit', [ArticleAdminController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article}', [ArticleAdminController::class, 'update'])->name('articles.update');

        Route::get('/membership-plans', [MembershipPlanAdminController::class, 'index'])->name('membership-plans.index');
        Route::post('/membership-plans', [MembershipPlanAdminController::class, 'store'])->name('membership-plans.store');
        Route::put('/membership-plans/{membershipPlan}', [MembershipPlanAdminController::class, 'update'])->name('membership-plans.update');
        Route::post('/membership-plans/{membershipPlan}/toggle', [MembershipPlanAdminController::class, 'toggle'])->name('membership-plans.toggle');
        Route::delete('/membership-plans/{membershipPlan}', [MembershipPlanAdminController::class, 'destroy'])->name('membership-plans.destroy');

        Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserAdminController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');

        Route::get('/settings', [SettingsAdminController::class, 'index'])->name('settings.index');
        Route::put('/settings/general', [SettingsAdminController::class, 'updateGeneral'])->name('settings.general');
        Route::post('/settings/categories', [SettingsAdminController::class, 'storeCategory'])->name('settings.categories.store');
        Route::put('/settings/categories/{category}', [SettingsAdminController::class, 'updateCategory'])->name('settings.categories.update');
        Route::delete('/settings/categories/{category}', [SettingsAdminController::class, 'destroyCategory'])->name('settings.categories.destroy');

        Route::get('/submissions', [SubmissionAdminController::class, 'index'])->name('submissions.index');
        Route::get('/submissions/{submission}', [SubmissionAdminController::class, 'show'])->name('submissions.show');
        Route::post('/submissions/{submission}/assign-reviewer', [SubmissionAdminController::class, 'assignReviewer'])->name('submissions.assign-reviewer');
        Route::post('/submissions/{submission}/review-type', [SubmissionAdminController::class, 'updateReviewType'])->name('submissions.update-review-type');
        Route::post('/submissions/{submission}/publish', [SubmissionAdminController::class, 'publishToIssue'])->name('submissions.publish');
    });

    Route::prefix('j/{journal}/manage')->name('journal.manage.')->middleware('journal.manage')->group(function () {
        Route::get('/', JournalManageDashboardController::class)->name('dashboard');

        Route::get('/settings', [JournalManageSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [JournalManageSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/categories', [JournalManageSettingsController::class, 'storeCategory'])->name('settings.categories.store');
        Route::put('/settings/categories/{category}', [JournalManageSettingsController::class, 'updateCategory'])->name('settings.categories.update');
        Route::delete('/settings/categories/{category}', [JournalManageSettingsController::class, 'destroyCategory'])->name('settings.categories.destroy');

        Route::post('/editorial-board', [JournalManageEditorialBoardController::class, 'store'])->name('editorial-board.store');
        Route::delete('/editorial-board/{member}', [JournalManageEditorialBoardController::class, 'destroy'])->name('editorial-board.destroy');

        Route::get('/volumes', [JournalManageVolumeController::class, 'index'])->name('volumes.index');
        Route::post('/volumes', [JournalManageVolumeController::class, 'store'])->name('volumes.store');
        Route::put('/volumes/{volume}', [JournalManageVolumeController::class, 'update'])->name('volumes.update');
        Route::post('/volumes/{volume}/issues', [JournalManageVolumeController::class, 'storeIssue'])->name('issues.store');
        Route::put('/volumes/{volume}/issues/{issue}', [JournalManageVolumeController::class, 'updateIssue'])->name('issues.update');

        Route::get('/articles', [JournalManageArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/create', [JournalManageArticleController::class, 'create'])->name('articles.create');
        Route::post('/articles/extract', [JournalManageArticleController::class, 'extract'])->name('articles.extract');
        Route::post('/articles/quick-volume', [JournalManageArticleController::class, 'quickVolume'])->name('articles.quick-volume');
        Route::post('/articles/quick-issue', [JournalManageArticleController::class, 'quickIssue'])->name('articles.quick-issue');
        Route::post('/articles/quick-category', [JournalManageArticleController::class, 'quickCategory'])->name('articles.quick-category');
        Route::post('/articles', [JournalManageArticleController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}/edit', [JournalManageArticleController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article}', [JournalManageArticleController::class, 'update'])->name('articles.update');

        Route::get('/submissions', [JournalManageSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/submissions/{submission}', [JournalManageSubmissionController::class, 'show'])->name('submissions.show');
        Route::post('/submissions/{submission}/assign-reviewer', [JournalManageSubmissionController::class, 'assignReviewer'])->name('submissions.assign-reviewer');
        Route::post('/submissions/{submission}/review-type', [JournalManageSubmissionController::class, 'updateReviewType'])->name('submissions.update-review-type');
        Route::post('/submissions/{submission}/publish', [JournalManageSubmissionController::class, 'publishToIssue'])->name('submissions.publish');

        Route::get('/announcements', [JournalManageAnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/announcements/create', [JournalManageAnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/announcements', [JournalManageAnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/announcements/{announcement}/edit', [JournalManageAnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('/announcements/{announcement}', [JournalManageAnnouncementController::class, 'update'])->name('announcements.update');
        Route::post('/announcements/{announcement}/close', [JournalManageAnnouncementController::class, 'close'])->name('announcements.close');
        Route::delete('/announcements/{announcement}', [JournalManageAnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('/reviewer-requests', [JournalManageReviewerRequestController::class, 'index'])->name('reviewer-requests.index');
        Route::post('/reviewer-requests/{reviewerRequest}/approve', [JournalManageReviewerRequestController::class, 'approve'])->name('reviewer-requests.approve');
        Route::post('/reviewer-requests/{reviewerRequest}/reject', [JournalManageReviewerRequestController::class, 'reject'])->name('reviewer-requests.reject');

        Route::get('/membership-plans', [JournalManageMembershipPlanController::class, 'index'])->name('membership-plans.index');
        Route::post('/membership-plans', [JournalManageMembershipPlanController::class, 'store'])->name('membership-plans.store');
        Route::put('/membership-plans/{membershipPlan}', [JournalManageMembershipPlanController::class, 'update'])->name('membership-plans.update');
        Route::post('/membership-plans/{membershipPlan}/toggle', [JournalManageMembershipPlanController::class, 'toggle'])->name('membership-plans.toggle');
        Route::delete('/membership-plans/{membershipPlan}', [JournalManageMembershipPlanController::class, 'destroy'])->name('membership-plans.destroy');
    });
});

require __DIR__.'/auth.php';
