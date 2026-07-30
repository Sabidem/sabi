<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AssessmentConfigController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AttendanceCorrectionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildAttendanceController;
use App\Http\Controllers\Api\ChildResultsController;
use App\Http\Controllers\Api\ChildrenController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EmergencyContactController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TermController;
use App\Http\Controllers\Api\TimetableController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TeacherReportController;
use App\Http\Controllers\Api\LoginHistoryController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\ScoreHistoryController;
use App\Http\Controllers\Api\StudentSettingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CampusController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/invitations/accept', [InvitationController::class, 'accept']);

// Public OTP password reset (pre-auth, email-keyed). Rate-limited per IP.
Route::post('/forgot-password', [PasswordResetController::class, 'requestOtp'])->middleware('throttle:6,1');
Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])->middleware('throttle:6,1');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:6,1');

// ── Authenticated + tenant-scoped ───────────────────────────────────────────
Route::middleware(['auth:sanctum', ResolveTenant::class])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Reads (any authenticated user in tenant; some filtered by role in-controller) ──
    Route::get('/classes', [ClassController::class, 'index']);
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/timetable', [TimetableController::class, 'index']);
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::get('/teachers/{id}', [TeacherController::class, 'show']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/assessment-configs', [AssessmentConfigController::class, 'index']);
    Route::get('/results', [ResultController::class, 'index']);
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::get('/terms', [TermController::class, 'index']);
    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::get('/lessons', [LessonController::class, 'index']);
    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::get('/submissions', [SubmissionController::class, 'index']);
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/messages', [MessageController::class, 'index']);

    // Per-user self-service (auth+tenant only).
    Route::get('/score-history', [ScoreHistoryController::class, 'index']);
    Route::get('/settings', [StudentSettingController::class, 'show']);
    Route::put('/settings', [StudentSettingController::class, 'update']);

    // School config reads (any authenticated tenant user).
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/campuses', [CampusController::class, 'index']);
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/sections', [SectionController::class, 'index']);

    // Parent portal (controllers verify the parent→child link).
    Route::get('/children', [ChildrenController::class, 'index']);
    Route::get('/child-results', [ChildResultsController::class, 'index']);
    Route::get('/child-attendance', [ChildAttendanceController::class, 'index']);
    Route::get('/emergency-contacts', [EmergencyContactController::class, 'index']);

    // Creator / marketplace (Course is creator-owned, NOT tenant-scoped).
    Route::get('/marketplace/courses', [CourseController::class, 'marketplace']);
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/revenue', [CourseController::class, 'revenue']);

    // ── Policy-gated writes (route needs only auth+tenant; controller authorizes) ──
    Route::post('/classes', [ClassController::class, 'store']);            // SchoolClassPolicy
    Route::post('/results', [ResultController::class, 'store']);            // ResultPolicy@create
    Route::post('/results/{id}/approve', [ResultController::class, 'approve']);
    Route::post('/results/{id}/publish', [ResultController::class, 'publish']);
    Route::post('/results/{id}/reject', [ResultController::class, 'reject']);
    Route::post('/lessons', [LessonController::class, 'store']);            // LessonPolicy
    Route::post('/assignments', [AssignmentController::class, 'store']);    // AssignmentPolicy
    Route::post('/submissions', [SubmissionController::class, 'store']);    // SubmissionPolicy@create (student)
    Route::post('/submissions/{id}/grade', [SubmissionController::class, 'grade']); // SubmissionPolicy@grade
    Route::put('/courses/{id}', [CourseController::class, 'update']);        // CoursePolicy (owner)
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);    // CoursePolicy (owner)

    // Owned writes (current user is the actor; controller scopes to them).
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/emergency-contacts', [EmergencyContactController::class, 'store']);
    // A student flags one of their own attendance days (student_id is forced to
    // the authenticated user in the controller).
    Route::post('/attendance-corrections', [AttendanceCorrectionController::class, 'store']);

    // ── Role-gated writes ──
    Route::middleware('role:school_admin,teacher')->group(function () {
        Route::post('/attendance', [AttendanceController::class, 'store']);
        // Attendance correction review queue (admin + teacher).
        Route::get('/attendance-corrections', [AttendanceCorrectionController::class, 'index']);
        Route::post('/attendance-corrections/{id}/approve', [AttendanceCorrectionController::class, 'approve']);
        Route::post('/attendance-corrections/{id}/reject', [AttendanceCorrectionController::class, 'reject']);
        Route::get('/teacher-reports/performance', [TeacherReportController::class, 'performance']);
        Route::get('/teacher-reports/completion', [TeacherReportController::class, 'completion']);
    });

    Route::middleware('role:creator')->group(function () {
        Route::post('/courses', [CourseController::class, 'store']);
    });

    Route::middleware('role:school_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::post('/timetable', [TimetableController::class, 'store']);
        Route::post('/invitations', [InvitationController::class, 'store']);
        Route::post('/students', [StudentController::class, 'store']);
        Route::post('/enrollments', [EnrollmentController::class, 'store']);
        Route::post('/assessment-configs', [AssessmentConfigController::class, 'store']);
        Route::post('/sessions', [SessionController::class, 'store']);
        Route::post('/sessions/{id}/set-current', [SessionController::class, 'setCurrent']);
        Route::post('/terms', [TermController::class, 'store']);
        Route::post('/terms/{id}/set-current', [TermController::class, 'setCurrent']);
        Route::post('/holidays', [HolidayController::class, 'store']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::post('/parent-links', [GuardianController::class, 'store']);

        // School config writes.
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile', [ProfileController::class, 'update']);
        Route::post('/campuses', [CampusController::class, 'store']);
        Route::put('/campuses/{id}', [CampusController::class, 'update']);
        Route::delete('/campuses/{id}', [CampusController::class, 'destroy']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::post('/sections', [SectionController::class, 'store']);

        // Edit/delete for config entities (all tenant-scoped via findOrFail).
        Route::put('/classes/{id}', [ClassController::class, 'update']);
        Route::delete('/classes/{id}', [ClassController::class, 'destroy']);
        Route::put('/sections/{id}', [SectionController::class, 'update']);
        Route::delete('/sections/{id}', [SectionController::class, 'destroy']);
        Route::put('/subjects/{id}', [SubjectController::class, 'update']);
        Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);
        Route::put('/departments/{id}', [DepartmentController::class, 'update']);
        Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);
        Route::put('/sessions/{id}', [SessionController::class, 'update']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
        Route::put('/terms/{id}', [TermController::class, 'update']);
        Route::delete('/terms/{id}', [TermController::class, 'destroy']);
        Route::put('/holidays/{id}', [HolidayController::class, 'update']);
        Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);

        // Promotions/transfers, imports, user lifecycle, bulk publish, timetable delete.
        Route::get('/transfers', [TransferController::class, 'index']);
        Route::post('/transfers', [TransferController::class, 'store']);
        Route::post('/import/students', [ImportController::class, 'students']);
        Route::post('/import/teachers', [ImportController::class, 'teachers']);
        Route::post('/users/{id}/status', [UserController::class, 'setStatus']);
        Route::post('/users/{id}/role', [UserController::class, 'updateRole']);
        Route::post('/results/publish-all', [ResultController::class, 'publishAll']);
        Route::delete('/timetable/{id}', [TimetableController::class, 'destroy']);

        // Reports & analytics.
        Route::get('/reports/performance', [ReportController::class, 'performance']);
        Route::get('/reports/attendance', [ReportController::class, 'attendance']);
        Route::get('/reports/enrollment', [ReportController::class, 'enrollment']);
        Route::get('/reports/activity', [ReportController::class, 'activity']);

        // Security & audit.
        Route::get('/security/login-history', [LoginHistoryController::class, 'index']);
        Route::get('/security/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/security/sessions', [SecurityController::class, 'sessions']);
        Route::post('/security/sessions/{tokenId}/revoke', [SecurityController::class, 'revoke']);
    });
});
