<?php

namespace Modules\TrainingDiary\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\TrainingDiary\Enums\FeedbackStatus;
use Modules\TrainingDiary\Http\Requests\Admin\UpdateFeedbackRequest;

class FeedbackController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $statusFilter = FeedbackStatus::tryFrom((string) $status);

        $query = UserFeedback::query()
            ->where('app', config('trainingdiary.app_package'))
            ->whereNotNull('text');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter->value);
        }

        $feedbacks = $query
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = UserFeedback::query()
            ->where('app', config('trainingdiary.app_package'))
            ->whereNotNull('text')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('trainingdiary::admin.feedback.index', [
            'feedbacks' => $feedbacks,
            'currentStatus' => $statusFilter,
            'counts' => $counts,
        ]);
    }

    public function update(UpdateFeedbackRequest $request, UserFeedback $feedback): RedirectResponse
    {
        if ((int) $request->input('_feedback_id') !== $feedback->id
            || $feedback->app !== config('trainingdiary.app_package')) {
            abort(404);
        }

        $status = FeedbackStatus::from($request->validated('status'));
        $answer = $request->validated('admin_answer');

        $feedback->status = $status->value;
        $feedback->admin_answer = $answer;

        if ($status === FeedbackStatus::Answered && $feedback->answered_at === null) {
            $feedback->answered_at = now();
        }

        $feedback->save();

        return back()->with('saved_feedback_id', $feedback->id);
    }
}
