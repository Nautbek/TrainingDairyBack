<?php

namespace Modules\TrainingDiary\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackMessage;
use App\Models\FeedbackThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\TrainingDiary\Http\Requests\Admin\ReplyFeedbackThreadRequest;

/**
 * Админка обращений (фидбек-чат) Training Diary — работает с общей
 * сущностью FeedbackThread/FeedbackMessage (app/Models), отфильтрованной по
 * app = config('trainingdiary.app_package'), а не с легаси-таблицей
 * user_feedback (та вперемешку хранит и activity-пинги, своей "сущности"
 * обращения не образует — см. миграцию feedback_threads).
 */
class FeedbackController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $statusFilter = in_array($status, [
            FeedbackThread::STATUS_OPEN,
            FeedbackThread::STATUS_CLOSED,
            FeedbackThread::STATUS_DELETED_BY_USER,
        ], true) ? $status : null;

        $query = FeedbackThread::query()
            ->where('app', config('trainingdiary.app_package'))
            ->with(['messages', 'user']);

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        $threads = $query
            ->orderByDesc('updated_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = FeedbackThread::query()
            ->where('app', config('trainingdiary.app_package'))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('trainingdiary::admin.feedback.index', [
            'threads' => $threads,
            'currentStatus' => $statusFilter,
            'counts' => $counts,
        ]);
    }

    public function update(ReplyFeedbackThreadRequest $request, FeedbackThread $thread): RedirectResponse
    {
        if ((int) $request->input('_thread_id') !== $thread->id
            || $thread->app !== config('trainingdiary.app_package')) {
            abort(404);
        }

        $data = $request->validated();

        if (! empty($data['reply'])) {
            FeedbackMessage::query()->create([
                'thread_id' => $thread->id,
                'sender' => FeedbackMessage::SENDER_ADMIN,
                'body' => $data['reply'],
            ]);
        }

        $thread->status = $data['status'];
        $thread->save();

        return back()->with('saved_thread_id', $thread->id);
    }
}
