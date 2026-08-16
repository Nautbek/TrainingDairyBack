@php use Modules\TrainingDiary\Enums\FeedbackStatus; @endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Фидбек Training Diary — админка</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: #f8f8f7;
            color: #1b1b18;
            line-height: 1.5;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .list-meta {
            font-size: 0.8125rem;
            color: #706f6c;
            margin-bottom: 1rem;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tab {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid transparent;
            transition: opacity 0.15s;
            background: #ececeb;
            color: #1b1b18;
        }

        .tab:hover { opacity: 0.85; }

        .tab-new.active { background: #dbeafe; color: #1e3a8a; border-color: #93c5fd; }
        .tab-in_progress.active { background: #fef9c3; color: #854d0e; border-color: #fde047; }
        .tab-answered.active { background: #dcfce7; color: #166534; border-color: #86efac; }
        .tab-closed.active { background: #e5e5e3; color: #44403c; border-color: #d4d4d4; }
        .tab.active { font-weight: 600; }
        .tab:not(.active) { opacity: 0.75; }

        .feedback-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .feedback-item {
            background: #fff;
            border: 1px solid #e5e5e3;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }

        .feedback-item.saved {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .feedback-text {
            font-size: 0.9375rem;
            white-space: pre-wrap;
            margin-bottom: 0.5rem;
        }

        .feedback-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: #a8a7a4;
            margin-bottom: 0.75rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .status-new { background: #dbeafe; color: #1e3a8a; }
        .status-in_progress { background: #fef9c3; color: #854d0e; }
        .status-answered { background: #dcfce7; color: #166534; }
        .status-closed { background: #e5e5e3; color: #44403c; }

        .answer-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            border-top: 1px solid #f0f0ef;
            padding-top: 0.75rem;
        }

        .answer-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .field-select,
        .field-textarea {
            border: 1px solid #e5e5e3;
            border-radius: 0.25rem;
            font-size: 0.8125rem;
            font-family: inherit;
            background: #fff;
            color: #1b1b18;
            padding: 0.375rem 0.5rem;
        }

        .field-select:focus,
        .field-textarea:focus {
            outline: none;
            border-color: #a8a7a4;
        }

        .field-textarea {
            width: 100%;
            resize: vertical;
            min-height: 3.5rem;
        }

        .save-btn {
            padding: 0.375rem 0.875rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid #1b1b18;
            background: #1b1b18;
            color: #fff;
            align-self: flex-start;
        }

        .save-btn:hover { opacity: 0.9; }

        .field-error {
            font-size: 0.6875rem;
            color: #dc2626;
        }

        .flash {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .flash-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .flash-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .empty {
            text-align: center;
            padding: 3rem 1rem;
            color: #706f6c;
            font-size: 0.9375rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.375rem 0.75rem;
            border: 1px solid #e5e5e3;
            border-radius: 0.25rem;
            text-decoration: none;
            color: #1b1b18;
            font-size: 0.875rem;
            background: #fff;
        }

        .pagination a:hover { background: #f5f5f3; }

        .pagination .active span {
            background: #1b1b18;
            color: #fff;
            border-color: #1b1b18;
        }

        .pagination .disabled span {
            opacity: 0.4;
            cursor: default;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Фидбек — Training Diary</h1>

        @if (session('saved_feedback_id'))
            <div class="flash flash-success">Отзыв #{{ session('saved_feedback_id') }} сохранён</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <nav class="tabs">
            <a
                href="{{ route('trainingdiary.admin.feedback.index', [], false) }}"
                class="tab {{ $currentStatus === null ? 'active' : '' }}"
            >Все</a>

            @foreach (FeedbackStatus::cases() as $statusOption)
                <a
                    href="{{ route('trainingdiary.admin.feedback.index', ['status' => $statusOption->value], false) }}"
                    class="tab tab-{{ $statusOption->value }} {{ $currentStatus === $statusOption ? 'active' : '' }}"
                >
                    {{ $statusOption->label() }}
                    @if ($counts->has($statusOption->value))
                        ({{ $counts[$statusOption->value] }})
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($feedbacks->total() > 0)
            <div class="list-meta">
                {{ $feedbacks->firstItem() }}–{{ $feedbacks->lastItem() }} из {{ $feedbacks->total() }}
            </div>

            <div class="feedback-list">
                @foreach ($feedbacks as $feedback)
                    @php
                        $editing = (int) old('_feedback_id') === $feedback->id;
                        $statusValue = $editing ? old('status', $feedback->status) : $feedback->status;
                        $answerValue = $editing ? old('admin_answer', $feedback->admin_answer) : $feedback->admin_answer;
                        $currentStatusEnum = FeedbackStatus::tryFrom($feedback->status);
                    @endphp
                    <div @class([
                        'feedback-item',
                        'saved' => session('saved_feedback_id') === $feedback->id,
                    ])>
                        <div class="feedback-text">{{ $feedback->text }}</div>

                        <div class="feedback-meta">
                            <span>#{{ $feedback->id }}</span>
                            <span class="status-badge status-{{ $feedback->status }}">
                                {{ $currentStatusEnum?->label() ?? $feedback->status }}
                            </span>
                            <span>{{ optional($feedback->visit_date)->format('d.m.Y') }}</span>
                            @if ($feedback->visit_ip)
                                <span>{{ $feedback->visit_ip }}</span>
                            @endif
                            @if ($feedback->user_id)
                                <span>user #{{ $feedback->user_id }}</span>
                            @endif
                            @if ($feedback->answered_at)
                                <span>отвечено {{ $feedback->answered_at->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>

                        <form
                            class="answer-form"
                            method="POST"
                            action="{{ route('trainingdiary.admin.feedback.update', $feedback, false) }}"
                        >
                            @csrf
                            <input type="hidden" name="_feedback_id" value="{{ $feedback->id }}">

                            <div class="answer-row">
                                <select class="field-select" name="status">
                                    @foreach (FeedbackStatus::cases() as $statusOption)
                                        <option value="{{ $statusOption->value }}" @selected($statusValue === $statusOption->value)>
                                            {{ $statusOption->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($editing && $errors->has('status'))
                                <div class="field-error">{{ $errors->first('status') }}</div>
                            @endif

                            <textarea
                                class="field-textarea"
                                name="admin_answer"
                                placeholder="Ответ пользователю..."
                            >{{ $answerValue }}</textarea>
                            @if ($editing && $errors->has('admin_answer'))
                                <div class="field-error">{{ $errors->first('admin_answer') }}</div>
                            @endif

                            <button type="submit" class="save-btn">Сохранить</button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{ $feedbacks->links('trainingdiary::admin.partials.pagination') }}
        @else
            <div class="empty">Нет отзывов в этой категории</div>
        @endif
    </div>
</body>
</html>
