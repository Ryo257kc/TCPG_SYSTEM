<?php

use App\Http\Controllers\Admin\V2\BillingListV2Controller;
use App\Http\Controllers\Admin\V2\DashboardV2Controller;
use App\Http\Controllers\Admin\V2\InformationV2Controller;
use App\Http\Controllers\Admin\V2\MaintenanceV2Controller;
use App\Http\Controllers\Admin\V2\LoginV2Controller;
use App\Http\Controllers\Admin\V2\AccountingV2Controller;
use App\Http\Controllers\Admin\V2\Master\AllowanceV2Controller;
use App\Http\Controllers\Admin\V2\Master\CalendarV2Controller;
use App\Http\Controllers\Admin\V2\Master\CompanyV2Controller;
use App\Http\Controllers\Admin\V2\Master\StaffV2Controller;
use App\Http\Controllers\Admin\V2\Master\StoreV2Controller;
use App\Http\Controllers\Admin\V2\OnboardingRequestV2Controller;
use App\Http\Controllers\Admin\V2\PayrollV2Controller;
use App\Http\Controllers\Admin\V2\ProfileRequestV2Controller;
use App\Http\Controllers\Admin\V2\YearEndAdjustmentV2Controller;






// StaffPortal
use App\Http\Controllers\StaffPortal\AttendanceController;
use App\Http\Controllers\StaffPortal\AuthController;
use App\Http\Controllers\StaffPortal\DocumentsController;
use App\Http\Controllers\StaffPortal\MyPageController;
use App\Http\Controllers\StaffPortal\ApplyController;
use App\Http\Controllers\StaffPortal\StoreSalesController;

// office
use App\Http\Controllers\StaffPortal\OfficeController;
use App\Http\Controllers\StaffPortal\office\EntryController;
use App\Http\Controllers\StaffPortal\office\HighMedicalController;
use App\Http\Controllers\StaffPortal\office\HomeVisitCounterController;
use App\Http\Controllers\StaffPortal\office\InsurersController;
use App\Http\Controllers\StaffPortal\office\PaymentConfirmationController;

use App\Http\Controllers\StaffPortal\office\AddressController;
use App\Http\Controllers\StaffPortal\office\CashBookController;
use App\Http\Controllers\StaffPortal\office\StoreDailyReportController;


// home_visit
use App\Http\Controllers\StaffPortal\home_visit\DailyReportController;
use App\Http\Controllers\StaffPortal\home_visit\MonthlyVisitController;

use App\Http\Controllers\StaffPortal\home_visit\PatientController;
use App\Http\Controllers\StaffPortal\home_visit\ReceiptController;
use App\Http\Controllers\StaffPortal\home_visit\ReceiptSummaryController;
use App\Http\Controllers\StaffPortal\home_visit\SalesController;
use App\Http\Controllers\StaffPortal\home_visit\VisitController;



// hv_office
use App\Http\Controllers\StaffPortal\hv_office\DepositManagementController;
use App\Http\Controllers\StaffPortal\hv_office\DownloadController;
use App\Http\Controllers\StaffPortal\hv_office\DistanceController;
use App\Http\Controllers\StaffPortal\hv_office\PaymentConfirmedController;

use App\Http\Controllers\StaffPortal\OnboardingRequestController;
use App\Http\Controllers\StaffPortal\PayrollController;
use App\Http\Controllers\StaffPortal\ProfileRequestController;
use App\Http\Controllers\StaffPortal\ShiftController;
use App\Http\Controllers\StaffPortal\YearEnd\YearEndApplicationController;
use Illuminate\Support\Facades\Route;

// ログインページへリダイレクト
Route::get('/', function () {
    return redirect()->route('login.portal');
});

Route::get('/', function () {
    return redirect()->route('login.portal');
});

// admin 側の大枠開始
Route::prefix('admin')->group(function (): void {
    Route::get('/login', [LoginV2Controller::class, 'show'])->name('admin.login');
    Route::post('/login', [LoginV2Controller::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [LoginV2Controller::class, 'logout'])->name('admin.logout');

    // admin 側のログイン後だけ
    Route::middleware('admin.auth')->group(function (): void {
        Route::get('/', [DashboardV2Controller::class, 'index'])->name('admin.dashboard');
        Route::get('/sales', [DashboardV2Controller::class, 'sales'])->name('admin.sales');
        Route::get('/sales/csv', [DashboardV2Controller::class, 'salesCsv'])->name('admin.sales.csv');
        Route::get('/sales/pdf', [DashboardV2Controller::class, 'salesPdf'])->name('admin.sales.pdf');
        Route::get('/work/information', [InformationV2Controller::class, 'index'])->name('admin.work.information');
        Route::post('/work/information/save', [InformationV2Controller::class, 'save'])->name('admin.work.information.save');
        Route::get('/work/maintenance', [MaintenanceV2Controller::class, 'index'])->name('admin.work.maintenance');
        Route::post('/work/maintenance/save', [MaintenanceV2Controller::class, 'save'])->name('admin.work.maintenance.save');
        Route::delete('/work/maintenance/{maintenanceId}', [MaintenanceV2Controller::class, 'destroy'])->name('admin.work.maintenance.destroy');
        Route::get('/work/journal-entries', [AccountingV2Controller::class, 'journalEntries'])->name('admin.work.journal_entries');
        Route::get('/work/journal-entries/sakura-reward-print', [AccountingV2Controller::class, 'sakuraRewardPrint'])->name('admin.work.journal_entries.sakura_reward_print');
        Route::post('/work/journal-entries/import', [AccountingV2Controller::class, 'importJournalEntries'])->name('admin.work.journal_entries.import');
        Route::get('/work/journal-entries/import/review/{token}', [AccountingV2Controller::class, 'importJournalEntriesReview'])->name('admin.work.journal_entries.import_review');
        Route::post('/work/journal-entries/import/apply', [AccountingV2Controller::class, 'applyJournalEntriesReview'])->name('admin.work.journal_entries.import_apply');
        Route::post('/work/journal-entries/update', [AccountingV2Controller::class, 'updateJournalEntry'])->name('admin.work.journal_entries.update');
        Route::post('/work/journal-entries/group-update', [AccountingV2Controller::class, 'updateJournalEntryGroup'])->name('admin.work.journal_entries.group_update');
        Route::post('/work/journal-entries/delete', [AccountingV2Controller::class, 'deleteJournalEntry'])->name('admin.work.journal_entries.delete');
        Route::get('/work/billing-list', [BillingListV2Controller::class, 'index'])->name('admin.work.billing_list');
        Route::get('/year-end-adjustments', [YearEndAdjustmentV2Controller::class, 'index'])->name('admin.work.year_end_adjustments');
        Route::post('/year-end-adjustments/create-targets', [YearEndAdjustmentV2Controller::class, 'createTargets'])->name('admin.work.year_end_adjustments.create_targets');
        Route::post('/year-end-adjustments/publish-date', [YearEndAdjustmentV2Controller::class, 'savePublishDate'])->name('admin.work.year_end_adjustments.publish_date');
        Route::get('/year-end-adjustments/bulk-preview', [YearEndAdjustmentV2Controller::class, 'bulkPreview'])->name('admin.work.year_end_adjustments.bulk_preview');
        Route::get('/year-end-adjustments/{applicationId}', [YearEndAdjustmentV2Controller::class, 'show'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.show');
        Route::get('/year-end-adjustments/{applicationId}/hoken-preview', [YearEndAdjustmentV2Controller::class, 'hokenPreview'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.hoken.preview');
        Route::get('/year-end-adjustments/{applicationId}/kiso-preview', [YearEndAdjustmentV2Controller::class, 'kisoPreview'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.kiso.preview');
        Route::get('/year-end-adjustments/{applicationId}/fuyo-preview', [YearEndAdjustmentV2Controller::class, 'fuyoPreview'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.fuyo.preview');
        Route::get('/year-end-adjustments/{applicationId}/gensen-bo-preview', [YearEndAdjustmentV2Controller::class, 'gensenBoPreview'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.gensen_bo.preview');
        Route::get('/year-end-adjustments/{applicationId}/gensen-hyou-preview', [YearEndAdjustmentV2Controller::class, 'gensenHyouPreview'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.gensen_hyou.preview');
        Route::get('/year-end-adjustments/{applicationId}/hoken/{hokenNo}/certificate-preview', [YearEndAdjustmentV2Controller::class, 'hokenCertificatePreview'])->whereNumber('applicationId')->whereNumber('hokenNo')->name('admin.work.year_end_adjustments.hoken.certificate_preview');
        Route::get('/year-end-adjustments/{applicationId}/hoken/{hokenNo}/certificate-file', [YearEndAdjustmentV2Controller::class, 'hokenCertificateFile'])->whereNumber('applicationId')->whereNumber('hokenNo')->name('admin.work.year_end_adjustments.hoken.certificate_file');
        Route::get('/year-end-adjustments/{applicationId}/fuyo/{fuyoNo}/certificate-file', [YearEndAdjustmentV2Controller::class, 'fuyoCertificateFile'])->whereNumber('applicationId')->whereNumber('fuyoNo')->name('admin.work.year_end_adjustments.fuyo.certificate_file');
        Route::get('/year-end-adjustments/{applicationId}/previous-job/certificate-file', [YearEndAdjustmentV2Controller::class, 'previousJobCertificateFile'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.previous_job.certificate_file');
        Route::get('/year-end-adjustments/{applicationId}/personal-info/{type}/certificate-file', [YearEndAdjustmentV2Controller::class, 'personalInfoCertificateFile'])->whereNumber('applicationId')->whereIn('type', ['address', 'name', 'disability'])->name('admin.work.year_end_adjustments.personal_info.certificate_file');
        Route::get('/year-end-adjustments/{applicationId}/housing-loan/certificate-file', [YearEndAdjustmentV2Controller::class, 'housingLoanCertificateFile'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.housing_loan.certificate_file');
        Route::post('/year-end-adjustments/update-status', [YearEndAdjustmentV2Controller::class, 'updateStatus'])->name('admin.work.year_end_adjustments.update_status');
        Route::post('/year-end-adjustments/{applicationId}/confirm', [YearEndAdjustmentV2Controller::class, 'confirmApplication'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.confirm');
        Route::post('/year-end-adjustments/{applicationId}/return', [YearEndAdjustmentV2Controller::class, 'returnApplication'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.return');
        Route::post('/year-end-adjustments/{applicationId}/reflect', [YearEndAdjustmentV2Controller::class, 'reflectApplication'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.reflect');
        Route::post('/year-end-adjustments/delete-target', [YearEndAdjustmentV2Controller::class, 'deleteTarget'])->name('admin.work.year_end_adjustments.delete_target');
        Route::post('/year-end-adjustments/{applicationId}/calculate', [YearEndAdjustmentV2Controller::class, 'calculateSingle'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.calculate');
        Route::post('/year-end-adjustments/{applicationId}/nen-tyo-update', [YearEndAdjustmentV2Controller::class, 'updateNenTyo'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.nen_tyo.update');
        Route::post('/year-end-adjustments/{applicationId}/nen-tyo-lock-toggle', [YearEndAdjustmentV2Controller::class, 'toggleNenTyoLock'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.nen_tyo.lock_toggle');
        Route::post('/year-end-adjustments/{applicationId}/hoken', [YearEndAdjustmentV2Controller::class, 'createHoken'])->name('admin.work.year_end_adjustments.hoken.create');
        Route::post('/year-end-adjustments/{applicationId}/hoken/{hokenNo}', [YearEndAdjustmentV2Controller::class, 'updateHoken'])->name('admin.work.year_end_adjustments.hoken.update');
        Route::post('/year-end-adjustments/{applicationId}/hoken/{hokenNo}/delete', [YearEndAdjustmentV2Controller::class, 'deleteHoken'])->name('admin.work.year_end_adjustments.hoken.delete');
        Route::post('/year-end-adjustments/{applicationId}/housing-loan', [YearEndAdjustmentV2Controller::class, 'updateHousingLoanNenTyo'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.housing_loan.update');
        Route::post('/year-end-adjustments/{applicationId}/previous-job', [YearEndAdjustmentV2Controller::class, 'updatePreviousJobNenTyo'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.previous_job.update');
        Route::post('/year-end-adjustments/{applicationId}/personal-info', [YearEndAdjustmentV2Controller::class, 'updatePersonalInfoStaging'])->whereNumber('applicationId')->name('admin.work.year_end_adjustments.personal_info.update');
        Route::post('/year-end-adjustments/{applicationId}/fuyo/{fuyoNo}', [YearEndAdjustmentV2Controller::class, 'updateFuyoRow'])->whereNumber('applicationId')->whereNumber('fuyoNo')->name('admin.work.year_end_adjustments.fuyo.update');

        // 個人情報変更申請（氏名・住所・世帯主・通勤経路・扶養増減。年中いつでも利用可）
        Route::get('/profile-requests', [ProfileRequestV2Controller::class, 'index'])->name('admin.work.profile_requests');
        Route::get('/profile-requests/{requestId}', [ProfileRequestV2Controller::class, 'show'])->whereNumber('requestId')->name('admin.work.profile_requests.show');
        Route::post('/profile-requests/{requestId}/personal-info', [ProfileRequestV2Controller::class, 'updatePersonalInfoStaging'])->whereNumber('requestId')->name('admin.work.profile_requests.personal_info.update');
        Route::post('/profile-requests/{requestId}/fuyo/{fuyoNo}', [ProfileRequestV2Controller::class, 'updateFuyoRow'])->whereNumber('requestId')->whereNumber('fuyoNo')->name('admin.work.profile_requests.fuyo.update');
        Route::get('/profile-requests/{requestId}/fuyo/{fuyoNo}/certificate-file', [ProfileRequestV2Controller::class, 'fuyoCertificateFile'])->whereNumber('requestId')->whereNumber('fuyoNo')->name('admin.work.profile_requests.fuyo.certificate_file');
        Route::get('/profile-requests/{requestId}/name-certificate-file', [ProfileRequestV2Controller::class, 'nameChangeCertificateFile'])->whereNumber('requestId')->name('admin.work.profile_requests.name.certificate_file');
        Route::get('/profile-requests/{requestId}/address-certificate-file', [ProfileRequestV2Controller::class, 'addressChangeCertificateFile'])->whereNumber('requestId')->name('admin.work.profile_requests.address.certificate_file');
        Route::get('/profile-requests/{requestId}/commute-certificate-file', [ProfileRequestV2Controller::class, 'commuteChangeCertificateFile'])->whereNumber('requestId')->name('admin.work.profile_requests.commute.certificate_file');
        Route::post('/profile-requests/{requestId}/confirm', [ProfileRequestV2Controller::class, 'confirmApplication'])->whereNumber('requestId')->name('admin.work.profile_requests.confirm');
        Route::post('/profile-requests/{requestId}/return', [ProfileRequestV2Controller::class, 'returnApplication'])->whereNumber('requestId')->name('admin.work.profile_requests.return');
        Route::post('/profile-requests/{requestId}/reflect', [ProfileRequestV2Controller::class, 'reflectApplication'])->whereNumber('requestId')->name('admin.work.profile_requests.reflect');

        // 入社手続き申請（住所・連絡先・振込口座・通勤経路・扶養・マイナンバー証憑。新入社スタッフ向け）
        Route::get('/onboarding-requests', [OnboardingRequestV2Controller::class, 'index'])->name('admin.work.onboarding_requests');
        Route::get('/onboarding-requests/{requestId}', [OnboardingRequestV2Controller::class, 'show'])->whereNumber('requestId')->name('admin.work.onboarding_requests.show');
        Route::post('/onboarding-requests/{requestId}/basic-info', [OnboardingRequestV2Controller::class, 'updateBasicInfoStaging'])->whereNumber('requestId')->name('admin.work.onboarding_requests.basic_info.update');
        Route::post('/onboarding-requests/{requestId}/fuyo/{fuyoNo}', [OnboardingRequestV2Controller::class, 'updateFuyoRow'])->whereNumber('requestId')->whereNumber('fuyoNo')->name('admin.work.onboarding_requests.fuyo.update');
        Route::get('/onboarding-requests/{requestId}/fuyo/{fuyoNo}/certificate-file', [OnboardingRequestV2Controller::class, 'fuyoCertificateFile'])->whereNumber('requestId')->whereNumber('fuyoNo')->name('admin.work.onboarding_requests.fuyo.certificate_file');
        Route::get('/onboarding-requests/{requestId}/address-certificate-file', [OnboardingRequestV2Controller::class, 'addressCertificateFile'])->whereNumber('requestId')->name('admin.work.onboarding_requests.address.certificate_file');
        Route::get('/onboarding-requests/{requestId}/mynumber-certificate-file', [OnboardingRequestV2Controller::class, 'mynumberCertificateFile'])->whereNumber('requestId')->name('admin.work.onboarding_requests.mynumber.certificate_file');
        Route::get('/onboarding-requests/{requestId}/license-certificate-file', [OnboardingRequestV2Controller::class, 'licenseCertificateFile'])->whereNumber('requestId')->name('admin.work.onboarding_requests.license.certificate_file');
        Route::get('/onboarding-requests/{requestId}/residence-certificate-file', [OnboardingRequestV2Controller::class, 'residenceCertificateFile'])->whereNumber('requestId')->name('admin.work.onboarding_requests.residence.certificate_file');
        Route::get('/onboarding-requests/{requestId}/employment-insurance-certificate-file', [OnboardingRequestV2Controller::class, 'employmentInsuranceCertificateFile'])->whereNumber('requestId')->name('admin.work.onboarding_requests.employment_insurance.certificate_file');
        Route::get('/onboarding-requests/{requestId}/previous-job-certificate-file', [OnboardingRequestV2Controller::class, 'previousJobCertificateFile'])->whereNumber('requestId')->name('admin.work.onboarding_requests.previous_job.certificate_file');
        Route::post('/onboarding-requests/{requestId}/confirm', [OnboardingRequestV2Controller::class, 'confirmApplication'])->whereNumber('requestId')->name('admin.work.onboarding_requests.confirm');
        Route::post('/onboarding-requests/{requestId}/return', [OnboardingRequestV2Controller::class, 'returnApplication'])->whereNumber('requestId')->name('admin.work.onboarding_requests.return');
        Route::post('/onboarding-requests/{requestId}/reflect', [OnboardingRequestV2Controller::class, 'reflectApplication'])->whereNumber('requestId')->name('admin.work.onboarding_requests.reflect');

        Route::get('/work/billing-list/{invoiceId}/print', [BillingListV2Controller::class, 'print'])->name('admin.work.billing_list.print');
        Route::post('/work/billing-list/store', [BillingListV2Controller::class, 'storeInvoice'])->name('admin.work.billing_list.store');
        Route::post('/work/billing-list/update', [BillingListV2Controller::class, 'updateInvoice'])->name('admin.work.billing_list.update');
        Route::post('/work/billing-list/delete', [BillingListV2Controller::class, 'deleteInvoice'])->name('admin.work.billing_list.delete');
        Route::post('/work/billing-list/detail/store', [BillingListV2Controller::class, 'storeDetail'])->name('admin.work.billing_list.detail.store');
        Route::post('/work/billing-list/detail/update', [BillingListV2Controller::class, 'updateDetail'])->name('admin.work.billing_list.detail.update');
        Route::post('/work/billing-list/detail/delete', [BillingListV2Controller::class, 'deleteDetail'])->name('admin.work.billing_list.detail.delete');

        require __DIR__ . '/admin/attendance_v2.php';
        require __DIR__ . '/admin/payroll_v2.php';
        require __DIR__ . '/admin/paid_leave_v2.php';
        require __DIR__ . '/admin/report_v2.php';

        Route::get('/master/company', [CompanyV2Controller::class, 'index'])->name('admin.master.company');
        Route::post('/master/company/update', [CompanyV2Controller::class, 'update'])->name('admin.master.company.update');
        Route::post('/master/company/syaho', [CompanyV2Controller::class, 'storeShaho'])->name('admin.master.company.syaho.store');
        Route::post('/master/company/syaho/update', [CompanyV2Controller::class, 'updateShaho'])->name('admin.master.company.syaho.update');
        Route::post('/master/company/syaho/delete', [CompanyV2Controller::class, 'deleteShaho'])->name('admin.master.company.syaho.delete');
        Route::post('/master/company/rouho', [CompanyV2Controller::class, 'storeRouho'])->name('admin.master.company.rouho.store');
        Route::post('/master/company/rouho/update', [CompanyV2Controller::class, 'updateRouho'])->name('admin.master.company.rouho.update');
        Route::post('/master/company/rouho/delete', [CompanyV2Controller::class, 'deleteRouho'])->name('admin.master.company.rouho.delete');
        Route::post('/master/company/mayor', [CompanyV2Controller::class, 'storeMayor'])->name('admin.master.company.mayor.store');
        Route::post('/master/company/mayor/update', [CompanyV2Controller::class, 'updateMayor'])->name('admin.master.company.mayor.update');
        Route::post('/master/company/mayor/delete', [CompanyV2Controller::class, 'deleteMayor'])->name('admin.master.company.mayor.delete');
        Route::get('/master/staff', [StaffV2Controller::class, 'index'])->name('admin.master.staff');
        Route::get('/master/staff/permissions', [StaffV2Controller::class, 'permissions'])->name('admin.master.staff.permissions');
        Route::post('/master/staff/permissions', [StaffV2Controller::class, 'updatePermissions'])->name('admin.master.staff.permissions.update');
        Route::post('/master/staff', [StaffV2Controller::class, 'store'])->name('admin.master.staff.store');
        Route::post('/master/staff/update', [StaffV2Controller::class, 'update'])->name('admin.master.staff.update');
        Route::post('/master/staff/insurance', [StaffV2Controller::class, 'updateInsurance'])->name('admin.master.staff.insurance.update');
        Route::post('/master/staff/basic-shift', [StaffV2Controller::class, 'storeBasicShift'])->name('admin.master.staff.basic_shift.store');
        Route::post('/master/staff/basic-shift/create-week', [StaffV2Controller::class, 'storeBasicShiftWeek'])->name('admin.master.staff.basic_shift.create_week');
        Route::post('/master/staff/basic-shift/update', [StaffV2Controller::class, 'updateBasicShift'])->name('admin.master.staff.basic_shift.update');
        Route::post('/master/staff/kihon', [StaffV2Controller::class, 'storeKihon'])->name('admin.master.staff.kihon.store');
        Route::post('/master/staff/kihon/update', [StaffV2Controller::class, 'updateKihon'])->name('admin.master.staff.kihon.update');
        Route::post('/master/staff/kihon/delete', [StaffV2Controller::class, 'deleteKihon'])->name('admin.master.staff.kihon.delete');
        Route::post('/master/staff/shaho', [StaffV2Controller::class, 'storeShaho'])->name('admin.master.staff.shaho.store');
        Route::post('/master/staff/shaho/update', [StaffV2Controller::class, 'updateShaho'])->name('admin.master.staff.shaho.update');
        Route::post('/master/staff/shaho/delete', [StaffV2Controller::class, 'deleteShaho'])->name('admin.master.staff.shaho.delete');
        Route::post('/master/staff/resident', [StaffV2Controller::class, 'storeResident'])->name('admin.master.staff.resident.store');
        Route::post('/master/staff/resident/update', [StaffV2Controller::class, 'updateResident'])->name('admin.master.staff.resident.update');
        Route::post('/master/staff/resident/delete', [StaffV2Controller::class, 'deleteResident'])->name('admin.master.staff.resident.delete');
        Route::post('/master/staff/fuyo', [StaffV2Controller::class, 'storeFuyo'])->name('admin.master.staff.fuyo.store');
        Route::post('/master/staff/fuyo/update', [StaffV2Controller::class, 'updateFuyo'])->name('admin.master.staff.fuyo.update');
        Route::post('/master/staff/fuyo/delete', [StaffV2Controller::class, 'deleteFuyo'])->name('admin.master.staff.fuyo.delete');
        Route::get('/master/store', [StoreV2Controller::class, 'index'])->name('admin.master.store');
        Route::post('/master/store/update', [StoreV2Controller::class, 'update'])->name('admin.master.store.update');
        Route::get('/master/allowance', [AllowanceV2Controller::class, 'index'])->name('admin.master.allowance');
        Route::post('/master/allowance', [AllowanceV2Controller::class, 'update'])->name('admin.master.allowance.update');
        Route::get('/master/calendar', [CalendarV2Controller::class, 'index'])->name('admin.master.calendar');
        Route::post('/master/calendar/update', [CalendarV2Controller::class, 'update'])->name('admin.master.calendar.update');
        Route::post('/master/calendar/delete', [CalendarV2Controller::class, 'delete'])->name('admin.master.calendar.delete');
        Route::post('/master/calendar/import-public-holidays', [CalendarV2Controller::class, 'importPublicHolidays'])->name('admin.master.calendar.import-public-holidays');


        Route::post('/bonus/confirm', [PayrollV2Controller::class, 'bonusConfirm'])->name('admin.bonus.confirm');

        Route::post('/insurance/import', [EntryController::class, 'import'])->name('admin.insurance.import');
    });
});

// staff_portal 側の大枠開始
Route::prefix('staff')->middleware('staff.auth')->group(function (): void {
    Route::get('/', [AuthController::class, 'dashboard'])->name('staff_portal.dashboard');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/admin-login', [AuthController::class, 'adminLogin'])->withoutMiddleware('staff.auth')->name('staff_portal.admin_login');

    Route::get('/login', [AuthController::class, 'index'])->withoutMiddleware('staff.auth')->name('login.portal');
    Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware('staff.auth')->name('login.portal.submit');
    Route::get('/password/change', [AuthController::class, 'passwordChange'])->withoutMiddleware('staff.auth')->name('staff_portal.password_change');
    Route::post('/password/change', [AuthController::class, 'updatePassword'])->withoutMiddleware('staff.auth')->name('staff_portal.password_change.update');

    Route::get('/attendance/monthly', [AttendanceController::class, 'attendanceMonthly'])->name('attendance.monthly');
    Route::post('/attendance/monthly/apply', [AttendanceController::class, 'attendanceMonthlyApply'])->name('attendance.monthly.apply');

    Route::get('/attendance/edit/{timeNo}', [AttendanceController::class, 'attendanceEdit'])->name('attendance.edit');
    Route::post('/attendance/edit/{timeNo}', [AttendanceController::class, 'attendanceUpdate'])->name('attendance.update');
    Route::get('/attendance/punch', [AttendanceController::class, 'attendancePunch'])->name('attendance.punch');
    Route::post('/attendance/punch', [AttendanceController::class, 'attendancePunchStore'])->name('attendance.punch.store');

    Route::get('/attendance/management', [AttendanceController::class, 'management'])->name('attendance.management');
    Route::get('/attendance/management/{staffId}', [AttendanceController::class, 'managementDetail'])->name('attendance.management.detail');
    Route::post('/attendance/management/{staffId}/approve', [AttendanceController::class, 'managementApprove'])->name('admin.attendance.manage.approve');
    Route::post('/attendance/management/{staffId}/remand', [AttendanceController::class, 'managementRemand'])->name('admin.attendance.manage.remand');

    Route::get('/attendance/punch-list', [AttendanceController::class, 'punchList'])->name('attendance.punch-list');

    Route::get('/attendance/paid-leave', [AttendanceController::class, 'paidLeave'])->name('attendance.paid_leave');
    Route::get('/shift/change', [ShiftController::class, 'adminShiftChange'])->name('admin.shift.change');
    Route::post('/shift/change/{timeNo}', [ShiftController::class, 'adminShiftUpdate'])->name('admin.shift.inline_update');

    Route::get('/office/attendance', [ShiftController::class, 'officeAttendance'])->name('office.attendance');
    Route::post('/office/attendance/{timeNo}', [ShiftController::class, 'officeAttendanceUpdate'])->name('office.attendance.update');

    Route::get('/shift/basic', [ShiftController::class, 'adminBasicShift'])->name('admin.basic-shift');
    Route::post('/shift/basic/{shiftNo}', [ShiftController::class, 'adminBasicShiftUpdate'])->name('admin.basic-shift.inline_update');

    Route::get('/office/documents', [DocumentsController::class, 'index'])->name('office.documents');

    Route::get('/office/sales-menu', [OfficeController::class, 'salesMenu'])->name('office.sales.menu');
    Route::get('/office/sales', [OfficeController::class, 'sales'])->name('office.sales');
    Route::get('/office/sales/print', [OfficeController::class, 'salesPrint'])->name('office.sales.print');
    Route::get('/office/sales/uncollected', [OfficeController::class, 'uncollectedReceivables'])->name('office.sales.uncollected');
    Route::get('/office/sales/uncollected/print', [OfficeController::class, 'uncollectedReceivablesPrint'])->name('office.sales.uncollected.print');
    Route::get('/office/sales/uncollected/detail-print', [OfficeController::class, 'uncollectedReceivablesDetailPrint'])->name('office.sales.uncollected.detail_print');

    Route::get('/office/receipt', [OfficeController::class, 'receipt'])->name('office.receipt');

    Route::get('/payroll/payslip', [PayrollController::class, 'payslip'])->name('payslip');
    Route::get('/payroll/bonus', [PayrollController::class, 'bonus'])->name('bonus');


    // 店舗管理
    Route::get('/store/sales', [StoreSalesController::class, 'index'])->name('store.sales');
    Route::get('/store/sales/personal', [StoreSalesController::class, 'personal'])->name('store.sales.personal');
    Route::get('/store/sales/consignment', [StoreSalesController::class, 'consignment'])->name('store.sales.consignment');
    Route::get('/store/sales/daily', [StoreSalesController::class, 'daily'])->name('store.sales.daily');
    Route::get('/store/sales/monthly', [StoreSalesController::class, 'monthly'])->name('store.sales.monthly');



    // 各種申請
    Route::get('/apply', [ApplyController::class, 'index'])->name('apply');

    // 年末調整（スタッフ申請）
    Route::get('/year-end-adjustment', [YearEndApplicationController::class, 'index'])->name('year_end_adjustment');
    Route::post('/year-end-adjustment/personal-info', [YearEndApplicationController::class, 'updatePersonalInfo'])->name('year_end_adjustment.personal_info.update');
    Route::post('/year-end-adjustment/dependents', [YearEndApplicationController::class, 'updateDependents'])->name('year_end_adjustment.dependents.update');
    Route::post('/year-end-adjustment/spouse', [YearEndApplicationController::class, 'updateSpouse'])->name('year_end_adjustment.spouse.update');
    Route::post('/year-end-adjustment/insurance', [YearEndApplicationController::class, 'updateInsurance'])->name('year_end_adjustment.insurance.update');
    Route::post('/year-end-adjustment/insurance/copy-previous-year', [YearEndApplicationController::class, 'copyPreviousYearInsurance'])->name('year_end_adjustment.insurance.copy_previous_year');
    Route::post('/year-end-adjustment/previous-job', [YearEndApplicationController::class, 'updatePreviousJob'])->name('year_end_adjustment.previous_job.update');
    Route::post('/year-end-adjustment/housing-loan', [YearEndApplicationController::class, 'updateHousingLoan'])->name('year_end_adjustment.housing_loan.update');
    Route::post('/year-end-adjustment/submit', [YearEndApplicationController::class, 'submit'])->name('year_end_adjustment.submit');

    // 個人情報変更申請（氏名・住所・世帯主・通勤経路・扶養増減。年中いつでも利用可）
    Route::get('/profile-request', [ProfileRequestController::class, 'index'])->name('profile_request');
    Route::post('/profile-request/personal-info', [ProfileRequestController::class, 'updatePersonalInfo'])->name('profile_request.personal_info.update');
    Route::post('/profile-request/dependents', [ProfileRequestController::class, 'updateDependents'])->name('profile_request.dependents.update');
    Route::post('/profile-request/submit', [ProfileRequestController::class, 'submit'])->name('profile_request.submit');

    // 入社手続き申請（住所・連絡先・振込口座・通勤経路・扶養・マイナンバー証憑。新入社スタッフ向け）
    Route::get('/onboarding-request', [OnboardingRequestController::class, 'index'])->name('onboarding_request');
    Route::post('/onboarding-request/basic-info', [OnboardingRequestController::class, 'updateBasicInfo'])->name('onboarding_request.basic_info.update');
    Route::post('/onboarding-request/dependents', [OnboardingRequestController::class, 'updateDependents'])->name('onboarding_request.dependents.update');
    Route::post('/onboarding-request/submit', [OnboardingRequestController::class, 'submit'])->name('onboarding_request.submit');

    // マイページ
    Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage');
    Route::post('/mypage', [MyPageController::class, 'update'])->name('mypage.update');


    // 往診日報
    Route::get('/home_visit/daily_report', [DailyReportController::class, 'index'])->name('home_visit.daily_report');
    Route::get('/home_visit/daily_report/print', [DailyReportController::class, 'print'])->name('home_visit.daily_report.print');
    Route::get('/copy-week', [DailyReportController::class, 'copyWeek'])->name('home_visit.daily_report.copy_week');
    Route::post('/home_visit/daily_report/quick-store', [DailyReportController::class, 'quickStore'])->name('home_visit.daily_report.quick_store');
    Route::get('/home_visit/daily_report/{nippouNo}/edit', [DailyReportController::class, 'edit'])->name('home_visit.daily_report.edit');
    Route::post('/home_visit/daily_report/{nippouNo}/update', [DailyReportController::class, 'update'])->name('home_visit.daily_report.update');
    Route::post('/home_visit/daily_report/{nippouNo}/sales-amount', [DailyReportController::class, 'updateSalesAmount'])->name('home_visit.daily_report.sales_amount.update');
    Route::post('/home_visit/daily_report/{nippouNo}/uncollected-amount', [DailyReportController::class, 'updateUncollectedAmount'])->name('home_visit.daily_report.uncollected_amount.update');
    Route::post('/home_visit/daily_report/{nippouNo}/delete', [DailyReportController::class, 'destroy'])->name('home_visit.daily_report.destroy');
    Route::post('/staff-portal/home-visit/receipts/{id}/update', [ReceiptController::class, 'update'])->name('home_visit.receipts.update');
    Route::delete('/staff-portal/home-visit/receipts/{id}', [ReceiptController::class, 'destroy'])->name('home_visit.receipts.destroy');
    Route::post('/staff/staff-portal/home-visit/receipts/store', [ReceiptController::class, 'store'])->name('home_visit.receipts.store');

    // 往診月間
    Route::get('/home_visit/monthly_report', [DailyReportController::class, 'monthlyIndex'])->name('home_visit.monthly_report');
    Route::post('/home_visit/monthly_report/confirm', [DailyReportController::class, 'confirm'])->name('home_visit.monthly_report.confirm');
    Route::post('/home_visit/monthly_report/adminConfirm', [DailyReportController::class, 'adminConfirm'])->name('home_visit.monthly_report.adminConfirm');
    Route::post('/home_visit/monthly_report/unconfirm', [DailyReportController::class, 'unconfirm'])->name('home_visit.monthly_report.unconfirm');
    Route::get('/home_visit/monthly_visit', [MonthlyVisitController::class, 'index'])->name('home_visit.monthly_visit');
    Route::get('/home_visit/monthly_visit/print', [MonthlyVisitController::class, 'print'])->name('home_visit.monthly_visit.print');
    Route::post('/home_visit/monthly_visit/{daily_report_id}/update', [MonthlyVisitController::class, 'update'])->name('home_visit.monthly_visit.update');

    // 往診患者
    Route::get('/home_visit/patients', [PatientController::class, 'index'])->name('home_visit.patients.index');
    Route::get('/home_visit/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/home_visit/patients/store', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/home_visit/patients/{patient_id}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::post('/home_visit/patients/{patient_id}/update', [PatientController::class, 'update'])->name('patients.update');
    Route::get('/home_visit/patient-info/{patientId}', [DailyReportController::class, 'patientInfo'])->name('home_visit.patient_info');

    // 往診入金管理
    Route::get('/home_visit/receipts', [ReceiptController::class, 'index'])->name('home_visit.receipts');
    Route::get('/home_visit/receipts/print-receipt', [ReceiptController::class, 'printReceipt'])->name('home_visit.receipts.print_receipt');
    Route::get('/home_visit/receipt-summary', [ReceiptSummaryController::class, 'index'])->name('home_visit.receipt_summary');
    Route::post('/home_visit/receipt-summary/confirm', [ReceiptSummaryController::class, 'confirm'])->name('home_visit.receipt_summary.confirm');
    Route::get('/home_visit/sales', [SalesController::class, 'index'])->name('home_visit.sales');




    // 往診事務
    Route::get('/hv_office/deposit_management/print', [DepositManagementController::class, 'print'])->name('hv_office.deposit_management.print');
    Route::get('/hv_office/download', [DownloadController::class, 'index'])->name('hv_office.download');
    Route::get('/hv_office/download/csv', [DownloadController::class, 'csv'])->name('hv_office.download.csv');
    Route::get('/hv_office/distance', [DistanceController::class, 'index'])->name('hv_office.distance');
    Route::get('/hv_office/distance/print', [DistanceController::class, 'print'])->name('hv_office.distance.print');
    Route::get('/hv_office/payment_confirmed', [PaymentConfirmedController::class, 'index'])->name('hv_office.payment_confirmed');
    Route::get('/hv_office/payment_confirmed/management', [PaymentConfirmedController::class, 'management'])->name('hv_office.payment_confirmed.management');
    Route::post('/hv_office/payment_confirmed/confirm', [PaymentConfirmedController::class, 'confirm'])->name('hv_office.payment_confirmed.confirm');
    Route::post('/hv_office/payment_confirmed/unconfirm', [PaymentConfirmedController::class, 'unconfirm'])->name('hv_office.payment_confirmed.unconfirm');

    // 事務所MENU
    Route::get('/office/office_menu', [OfficeController::class, 'officeMenu'])->name('office.office_menu');
    Route::get('/office/store_daily_report', [StoreDailyReportController::class, 'index'])->name('office.store_daily_report');
    Route::get('/office/store_daily_report/daily_summary', [StoreDailyReportController::class, 'dailySummary'])->name('office.store_daily_report.daily_summary');
    Route::get('/office/store_daily_report/daily_summary/monthly-print', [StoreDailyReportController::class, 'dailySummaryMonthlyPrint'])->name('office.store_daily_report.daily_summary.monthly_print');
    Route::get('/office/store_daily_report/request_history', [StoreDailyReportController::class, 'requestHistory'])->name('office.store_daily_report.request_history');
    Route::get('/office/store_daily_report/return_note', [StoreDailyReportController::class, 'returnNote'])->name('office.store_daily_report.return_note');
    Route::get('/office/store_daily_report/other_list', [StoreDailyReportController::class, 'otherList'])->name('office.store_daily_report.other_list');
    Route::get('/office/store_daily_report/other_list/print', [StoreDailyReportController::class, 'otherListPrint'])->name('office.store_daily_report.other_list.print');
    Route::get('/office/store_daily_report/item_summary', [StoreDailyReportController::class, 'itemSummary'])->name('office.store_daily_report.item_summary');
    Route::post('/office/store_daily_report/request_history/save', [StoreDailyReportController::class, 'saveRequestHistory'])->name('office.store_daily_report.request_history.save');
    Route::post('/office/store_daily_report/return_note/save', [StoreDailyReportController::class, 'saveReturnNote'])->name('office.store_daily_report.return_note.save');
    Route::get('/office/store_daily_report/daily_summary/detail', [StoreDailyReportController::class, 'dailySummaryDetail'])->name('office.store_daily_report.daily_summary.detail');
    Route::get('/office/store_daily_report/daily_summary/print', [StoreDailyReportController::class, 'dailySummaryPrint'])->name('office.store_daily_report.daily_summary.print');
    Route::post('/office/store_daily_report/daily_summary/detail/header-save', [StoreDailyReportController::class, 'saveDailySummaryHeader'])->name('office.store_daily_report.daily_summary.detail.header_save');
    Route::post('/office/store_daily_report/daily_summary/summary-save', [StoreDailyReportController::class, 'saveDailySummarySummary'])->name('office.store_daily_report.daily_summary.summary_save');
    Route::post('/office/store_daily_report/daily_summary/detail/add-patient', [StoreDailyReportController::class, 'addDailySummaryPatient'])->name('office.store_daily_report.daily_summary.detail.add_patient');
    Route::post('/office/store_daily_report/daily_summary/detail/bulk-ch', [StoreDailyReportController::class, 'bulkCheckDailySummaryDetail'])->name('office.store_daily_report.daily_summary.detail.bulk_ch');
    Route::post('/office/store_daily_report/daily_summary/detail/save', [StoreDailyReportController::class, 'saveDailySummaryDetail'])->name('office.store_daily_report.daily_summary.detail.save');
    Route::post('/office/store_daily_report/daily_summary/detail/expense-save', [StoreDailyReportController::class, 'saveDailySummaryExpense'])->name('office.store_daily_report.daily_summary.detail.expense_save');
    Route::post('/office/store_daily_report/daily_summary/monthly-window-save', [StoreDailyReportController::class, 'saveMonthlyWindowInput'])->name('office.store_daily_report.daily_summary.monthly_window_save');
    Route::post('/office/store_daily_report/daily_summary/monthly-close', [StoreDailyReportController::class, 'closeDailySummaryMonthly'])->name('office.store_daily_report.daily_summary.monthly_close');
    Route::post('/office/store_daily_report/daily_summary/monthly-unlock', [StoreDailyReportController::class, 'unlockDailySummaryMonthly'])->name('office.store_daily_report.daily_summary.monthly_unlock');
    Route::get('/office/office_menu/address', [AddressController::class, 'index'])->name('office.office_menu.address');
    Route::get('/office/office_menu/cash_book', [CashBookController::class, 'index'])->name('office.office_menu.cash_book');
    Route::post('/office/office_menu/cash_book/save', [CashBookController::class, 'save'])->name('office.office_menu.cash_book.save');
    Route::post('/office/office_menu/cash_book/delete', [CashBookController::class, 'delete'])->name('office.office_menu.cash_book.delete');
    Route::post('/office/office_menu/cash_book/monthly-close', [CashBookController::class, 'closeMonthly'])->name('office.office_menu.cash_book.monthly_close');
    Route::post('/office/office_menu/cash_book/monthly-unlock', [CashBookController::class, 'unlockMonthly'])->name('office.office_menu.cash_book.monthly_unlock');

    // EntryController
    Route::get('/office/receipt/entry', [EntryController::class, 'index'])->name('office.receipt.entry');
    Route::post('/office/receipt/entry/import', [EntryController::class, 'import'])->name('office.receipt.entry.import');
    Route::post('/office/receipt/entry/save', [EntryController::class, 'save'])->name('office.receipt.entry.save');
    Route::post('/office/receipt/entry/delete', [EntryController::class, 'delete'])->name('office.receipt.entry.delete');
    Route::post('/office/receipt/entry/monthly-close', [EntryController::class, 'closeMonthly'])->name('office.receipt.entry.monthly_close');
    Route::post('/office/receipt/entry/monthly-unlock', [EntryController::class, 'unlockMonthly'])->name('office.receipt.entry.monthly_unlock');
    // PaymentConfirmationController
    Route::get('/office/receipt/payment-confirmation', [PaymentConfirmationController::class, 'index'])->name('office.receipt.payment_confirmation');
    Route::post('/office/receipt/payment-confirmation/save', [PaymentConfirmationController::class, 'save'])->name('office.receipt.payment_confirmation.save');
    Route::post('/office/receipt/payment-confirmation/delete', [PaymentConfirmationController::class, 'delete'])->name('office.receipt.payment_confirmation.delete');
    // HomeVisitCounterController
    Route::get('/office/receipt/home-visit-counter', [HomeVisitCounterController::class, 'index'])->name('office.receipt.home_visit_counter');
    Route::post('/office/receipt/home-visit-counter/save', [HomeVisitCounterController::class, 'save'])->name('office.receipt.home_visit_counter.save');
    Route::post('/office/receipt/home-visit-counter/delete', [HomeVisitCounterController::class, 'delete'])->name('office.receipt.home_visit_counter.delete');
    // InsurersController
    Route::get('/office/receipt/insurers', [InsurersController::class, 'index'])->name('office.receipt.insurers');
    Route::post('/office/receipt/insurers/save', [InsurersController::class, 'save'])->name('office.receipt.insurers.save');
    Route::post('/office/receipt/insurers/delete', [InsurersController::class, 'delete'])->name('office.receipt.insurers.delete');
    // HighMedical
    Route::get('/office/receipt/high_medical', [HighMedicalController::class, 'index'])->name('office.receipt.high_medical');
    Route::post('/office/receipt/high_medical/save', [HighMedicalController::class, 'save'])->name('office.receipt.high_medical.save');
});
