<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserFeedback;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    /**
     * Отображение страницы с отзывами
     */
    public function index(): View
    {
        $perPage = 20;
        $currentPage = request()->get('page', 1);
        
        // Получаем общее количество отзывов
        $total = DB::table('user_feedback')
            ->whereNotNull('text')
            ->count();
        
        // Получаем отзывы с пагинацией
        $feedbacks = DB::table('user_feedback')
            ->whereNotNull('text')
            ->orderBy('visit_date', 'desc')
            ->orderBy('visit_ip', 'desc')
            ->offset(($currentPage - 1) * $perPage)
            ->limit($perPage)
            ->get();
        
        // Преобразуем в коллекцию моделей для удобства работы в view
        $feedbacksCollection = $feedbacks->map(function ($feedback) {
            $model = new UserFeedback();
            $model->setRawAttributes((array) $feedback);
            $model->exists = true;
            return $model;
        });
        
        // Создаем пагинатор
        $paginator = new LengthAwarePaginator(
            $feedbacksCollection,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('feedback.index', ['feedbacks' => $paginator]);
    }
}
