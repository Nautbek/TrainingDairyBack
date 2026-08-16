<?php

namespace Modules\TrainingDiary\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\TrainingDiary\Http\Requests\Admin\UpdateUserDiscountRequest;

/**
 * Персональные скидки на донат/подписку (App\Services\DonationPricingService)
 * — глобальные для юзера (таблица users одна на все приложения), поиск по
 * uuid. Держим здесь же, а не в отдельном общем модуле, потому что фидбэк
 * на годовую подписку/скидки пришёл именно от юзера Training Diary и это
 * единственная админка, которая сейчас у проекта есть.
 */
class UserDiscountController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $query = User::query()->whereNotNull('uuid');

        if ($search !== '') {
            $query->where('uuid', 'like', '%'.$search.'%');
        }

        $users = $query
            ->orderByDesc('discount_percent')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $lastApps = DB::table('user_visits')
            ->whereIn('user_id', $users->pluck('id'))
            ->whereNotNull('app')
            ->select('user_id', 'app')
            ->orderByDesc('visit_date')
            ->get()
            ->unique('user_id')
            ->pluck('app', 'user_id');

        return view('trainingdiary::admin.users.index', [
            'users' => $users,
            'search' => $search,
            'lastApps' => $lastApps,
        ]);
    }

    public function update(UpdateUserDiscountRequest $request, User $user): RedirectResponse
    {
        if ((int) $request->input('_user_id') !== $user->id) {
            abort(404);
        }

        $user->update([
            'discount_percent' => $request->validated('discount_percent'),
        ]);

        return back()->with('saved_user_id', $user->id);
    }
}
